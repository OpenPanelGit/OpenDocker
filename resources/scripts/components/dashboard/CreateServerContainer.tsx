import { useEffect, useState } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { useStoreState } from 'easy-peasy';
import http from '@/api/http';
import useFlash from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';
import { Form, Formik, Field } from 'formik';
import * as Yup from 'yup';
import { Server, Cpu, Layers, Folder, Plus } from '@gravity-ui/icons';
import styled from 'styled-components';

const Card = styled.div`
    background: #1a1a1a;
    padding: 2rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.05);
`;

const FormRow = styled.div`
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    
    @media (min-width: 768px) {
        grid-template-columns: 1fr 1fr;
    }
`;

const Label = styled.label`
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
`;

const Input = styled(Field)`
    width: 100%;
    background: #111;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.375rem;
    padding: 0.75rem;
    color: white;
    outline: none;
    transition: border-color 0.2s;
    
    &:focus {
        border-color: var(--brand);
    }
`;

const Select = styled(Field)`
    width: 100%;
    background: #111;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.375rem;
    padding: 0.75rem;
    color: white;
    outline: none;
    transition: border-color 0.2s;
    
    &:focus {
        border-color: var(--brand);
    }
`;

const ResourceBox = styled.div`
    background: #111;
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 0.5rem;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 0.5rem;
    
    &.active {
        border-color: rgba(var(--brand), 0.4);
        background: rgba(var(--brand), 0.05);
    }
`;

interface Nest {
    id: number;
    name: string;
    eggs: Egg[];
}

interface Egg {
    id: number;
    name: string;
}

const CreateServerContainer = () => {
    const { addFlash } = useFlash();
    const [loading, setLoading] = useState(true);
    const [nests, setNests] = useState<Nest[]>([]);
    const user = useStoreState((state) => state.user.data);

    useEffect(() => {
        http.get('/api/client/nests')
            .then(({ data }) => {
                // Handle Pterodactyl API response structure (data.data is the array)
                const rawNests = Array.isArray(data) ? data : (data.data || []);

                // Normalize nests to ensure we handle Pterodactyl's nested attributes/relationships
                const normalizedNests = rawNests.map((nest: any) => {
                    const attr = nest.attributes || nest;
                    const rels = nest.relationships || attr.relationships || {};
                    const eggsRaw = rels.eggs?.data || attr.eggs || [];

                    return {
                        id: attr.id,
                        name: attr.name,
                        eggs: Array.isArray(eggsRaw) ? eggsRaw.map((egg: any) => ({
                            id: egg.attributes?.id || egg.id,
                            name: egg.attributes?.name || egg.name,
                        })) : []
                    };
                });

                console.log('Normalized Nests:', normalizedNests);
                setNests(normalizedNests);
            })
            .catch(console.error)
            .finally(() => setLoading(false));
    }, []);

    const submit = (values: any, { setSubmitting }: any) => {
        http.post('/api/client/servers', values)
            .then(() => {
                addFlash({ type: 'success', message: 'Le serveur est en cours de création !' });
                window.location.href = '/';
            })
            .catch((error) => {
                addFlash({ type: 'error', message: error.response?.data?.error || 'Erreur lors de la création.' });
                setSubmitting(false);
            });
    };

    if (loading || !user) return <Spinner centered />;

    return (
        <PageContentBlock title={'Créer un Serveur'} showFlashKey={'create-server'}>
            <div className='max-w-4xl mx-auto'>
                <div className='mb-8'>
                    <h1 className='text-3xl font-black mb-2'>Déployer un nouveau serveur</h1>
                    <p className='text-white/40'>Utilisez vos ressources achetées pour créer une nouvelle instance instantanément.</p>
                </div>

                {user && (
                    <div className='grid grid-cols-2 md:grid-cols-4 gap-4 mb-8'>
                        <ResourceBox className='active'>
                            <Server width={20} height={20} className='text-brand' />
                            <p className='text-xs text-white/40 uppercase font-bold'>Slots</p>
                            <p className='text-xl font-bold'>{user?.boughtSlots || 0}</p>
                        </ResourceBox>
                        <ResourceBox>
                            <Cpu width={20} height={20} />
                            <p className='text-xs text-white/40 uppercase font-bold'>CPU</p>
                            <p className='text-xl font-bold'>{user!.boughtCpu || 0}%</p>
                        </ResourceBox>
                        <ResourceBox>
                            <Layers width={20} height={20} />
                            <p className='text-xs text-white/40 uppercase font-bold'>RAM</p>
                            <p className='text-xl font-bold'>{user?.boughtMemory || 0}MB</p>
                        </ResourceBox>
                        <ResourceBox>
                            <Folder width={20} height={20} />
                            <p className='text-xs text-white/40 uppercase font-bold'>Disk</p>
                            <p className='text-xl font-bold'>{user?.boughtDisk || 0}MB</p>
                        </ResourceBox>
                    </div>
                )}

                <Formik
                    enableReinitialize
                    initialValues={{
                        name: '',
                        nest_id: nests?.[0]?.id || 0,
                        egg_id: (nests?.[0]?.eggs && nests[0].eggs[0]?.id) || 0,
                        memory: user?.boughtMemory ?? 0,
                        cpu: user?.boughtCpu ?? 0,
                        disk: user?.boughtDisk ?? 0,
                        databases: user?.boughtDatabases ?? 0,
                        backups: user?.boughtBackups ?? 0,
                    }}
                    validationSchema={Yup.object().shape({
                        name: Yup.string().required().min(3),
                        nest_id: Yup.number().required(),
                        egg_id: Yup.number().required(),
                    })}
                    onSubmit={submit}
                >
                    {({ values, isSubmitting, setFieldValue }) => (
                        <Form>
                            <Card>
                                <FormRow>
                                    <div>
                                        <Label>Nom du Serveur</Label>
                                        <Input name='name' placeholder='Mon super serveur' />
                                    </div>
                                    <div>
                                        <Label>Catégorie (Nest)</Label>
                                        <Select
                                            as='select'
                                            name='nest_id'
                                            onChange={(e: any) => {
                                                const id = parseInt(e.target.value);
                                                setFieldValue('nest_id', id);
                                                const nest = nests?.find(n => n.id === id);
                                                if (nest && nest.eggs && nest.eggs.length > 0) {
                                                    setFieldValue('egg_id', nest.eggs[0].id);
                                                }
                                            }}
                                        >
                                            {Array.isArray(nests) && nests.map(nest => (
                                                <option key={nest.id} value={nest.id}>{nest.name}</option>
                                            ))}
                                        </Select>
                                    </div>
                                </FormRow>

                                <FormRow>
                                    <div>
                                        <Label>Type de Serveur (Egg)</Label>
                                        <Select as='select' name='egg_id'>
                                            {nests?.find(n => n.id === values.nest_id)?.eggs?.map(egg => (
                                                <option key={egg.id} value={egg.id}>{egg.name}</option>
                                            )) || <option disabled>Aucun egg disponible</option>}
                                        </Select>
                                    </div>
                                    <div className='flex items-end'>
                                        <p className='text-xs text-white/30 italic'>
                                            Le serveur sera déployé sur le premier nœud disponible avec assez de capacité.
                                        </p>
                                    </div>
                                </FormRow>

                                <div className='mt-8 pt-8 border-t border-white/5'>
                                    <h3 className='text-lg font-bold mb-4 flex items-center gap-x-2'>
                                        <Plus width={20} height={20} className='text-brand' />
                                        Allocation Automatique
                                    </h3>
                                    <p className='text-sm text-white/50 mb-6'>
                                        Toutes vos ressources achetées seront allouées à ce serveur. Vous pourrez les modifier plus tard si vous créez d'autres serveurs.
                                    </p>

                                    <button
                                        type='submit'
                                        disabled={isSubmitting || (user?.boughtSlots || 0) <= 0}
                                        className='w-full py-4 bg-brand hover:bg-brand-dark text-white font-black uppercase tracking-widest rounded-md transition-all shadow-xl shadow-brand/20 disabled:opacity-50 disabled:grayscale'
                                    >
                                        {isSubmitting ? 'Création en cours...' : 'Déployer maintenant'}
                                    </button>
                                    {(user?.boughtSlots || 0) <= 0 && (
                                        <p className='text-red-500 text-center text-sm mt-4 font-bold'>
                                            Vous n'avez plus de slots de serveur disponibles. Passez à la boutique !
                                        </p>
                                    )}
                                </div>
                            </Card>
                        </Form>
                    )}
                </Formik>
            </div>
        </PageContentBlock>
    );
};

export default CreateServerContainer;
