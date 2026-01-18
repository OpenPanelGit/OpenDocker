import { useEffect, useState } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { useStoreState } from 'easy-peasy';
import http from '@/api/http';
import useFlash from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';
import { Form, Formik, Field, ErrorMessage } from 'formik';
import * as Yup from 'yup';
import { Server, Folder, Plus } from '@gravity-ui/icons';
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

const Slider = styled.input`
    width: 100%;
    margin-top: 0.5rem;
    accent-color: var(--brand);
    cursor: pointer;
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

interface Limits {
    cpu: number;
    memory: number;
    disk: number;
    databases: number;
    backups: number;
}

const CreateServerContainer = () => {
    const { addFlash } = useFlash();
    const [loading, setLoading] = useState(true);
    const [nests, setNests] = useState<Nest[]>([]);
    const [limits, setLimits] = useState<Limits>({ cpu: 100, memory: 4096, disk: 10240, databases: 5, backups: 5 });
    const [available, setAvailable] = useState<any>({});
    const user = useStoreState((state) => state.user.data);

    useEffect(() => {
        Promise.all([
            http.get('/api/client/nests'),
            http.get('/api/client/store')
        ])
            .then(([{ data: nestData }, { data: storeData }]) => {
                const rawNests = Array.isArray(nestData) ? nestData : (nestData.data || []);
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
                setNests(normalizedNests);

                if (storeData.available) {
                    setAvailable(storeData.available);
                    setLimits({
                        cpu: storeData.limit_cpu,
                        memory: storeData.limit_memory,
                        disk: storeData.limit_disk,
                        databases: storeData.limit_databases,
                        backups: storeData.limit_backups
                    });
                }
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
                    <p className='text-white/40'>Personnalisez les ressources de votre instance selon vos besoins.</p>
                </div>

                <div className='grid grid-cols-2 md:grid-cols-4 gap-4 mb-8'>
                    <ResourceBox>
                        <Server width={20} height={20} className='text-brand' />
                        <p className='text-xs text-white/40 uppercase font-bold'>Slots Restants</p>
                        <p className='text-xl font-bold'>{available.slots || 0}</p>
                    </ResourceBox>
                    <ResourceBox>
                        <Plus width={20} height={20} className='text-brand' />
                        <p className='text-xs text-white/40 uppercase font-bold'>CPU Dispo.</p>
                        <p className='text-xl font-bold'>{available.cpu || 0}%</p>
                    </ResourceBox>
                    <ResourceBox>
                        <Plus width={20} height={20} className='text-brand' />
                        <p className='text-xs text-white/40 uppercase font-bold'>RAM Dispo.</p>
                        <p className='text-xl font-bold'>{available.memory || 0} MB</p>
                    </ResourceBox>
                    <ResourceBox>
                        <Folder width={20} height={20} className='text-brand' />
                        <p className='text-xs text-white/40 uppercase font-bold'>Disk Dispo.</p>
                        <p className='text-xl font-bold'>{available.disk || 0} MB</p>
                    </ResourceBox>
                </div>

                <Formik
                    enableReinitialize
                    initialValues={{
                        name: '',
                        nest_id: nests?.[0]?.id || 0,
                        egg_id: (nests?.[0]?.eggs && nests[0].eggs[0]?.id) || 0,
                        memory: Math.min(available.memory || 0, limits.memory),
                        cpu: Math.min(available.cpu || 0, limits.cpu),
                        disk: Math.min(available.disk || 0, limits.disk),
                        databases: Math.min(available.databases || 0, limits.databases),
                        backups: Math.min(available.backups || 0, limits.backups),
                    }}
                    validationSchema={Yup.object().shape({
                        name: Yup.string().required().min(3),
                        nest_id: Yup.number().required(),
                        egg_id: Yup.number().required(),
                        memory: Yup.number().required().min(128),
                        cpu: Yup.number().required().min(10),
                        disk: Yup.number().required().min(128),
                    })}
                    onSubmit={submit}
                >
                    {({ values, isSubmitting, setFieldValue, isValid, submitCount }) => (
                        <Form>
                            <Card>
                                <div className='mb-10'>
                                    <h2 className='text-xl font-bold mb-6 flex items-center gap-x-2'>
                                        <Server width={20} height={20} className='text-brand' />
                                        Informations Générales
                                    </h2>
                                    <FormRow>
                                        <div>
                                            <Label>Nom du Serveur</Label>
                                            <Input name='name' placeholder='Mon super serveur' />
                                            <ErrorMessage name='name' component='div' className='text-red-500 text-xs mt-1' />
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
                                            <ErrorMessage name='nest_id' component='div' className='text-red-500 text-xs mt-1' />
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
                                            <ErrorMessage name='egg_id' component='div' className='text-red-500 text-xs mt-1' />
                                        </div>
                                    </FormRow>
                                </div>

                                <div className='mt-8 pt-8 border-t border-white/5'>
                                    <h2 className='text-xl font-bold mb-6 flex items-center gap-x-2'>
                                        <Plus width={20} height={20} className='text-brand' />
                                        Allocation des Ressources
                                    </h2>

                                    <div className='grid grid-cols-1 md:grid-cols-2 gap-8'>
                                        <div className='bg-[#111] p-4 rounded-lg border border-white/5'>
                                            <div className='flex justify-between items-center mb-1'>
                                                <Label style={{ marginBottom: 0 }}>CPU (%)</Label>
                                                <span className='text-brand font-bold'>{values.cpu}%</span>
                                            </div>
                                            <p className='text-xs text-white/30 mb-2'>Maximum: {Math.min(available.cpu || 0, limits.cpu)}%</p>
                                            <Slider
                                                type='range'
                                                name='cpu'
                                                min={10}
                                                max={Math.min(available.cpu || 0, limits.cpu)}
                                                step={10}
                                                value={values.cpu}
                                                onChange={e => setFieldValue('cpu', parseInt(e.target.value))}
                                            />
                                            <ErrorMessage name='cpu' component='div' className='text-red-500 text-xs mt-1' />
                                        </div>

                                        <div className='bg-[#111] p-4 rounded-lg border border-white/5'>
                                            <div className='flex justify-between items-center mb-1'>
                                                <Label style={{ marginBottom: 0 }}>Mémoire (RAM)</Label>
                                                <span className='text-brand font-bold'>{values.memory} MB</span>
                                            </div>
                                            <p className='text-xs text-white/30 mb-2'>Maximum: {Math.min(available.memory || 0, limits.memory)} MB</p>
                                            <Slider
                                                type='range'
                                                name='memory'
                                                min={128}
                                                max={Math.min(available.memory || 0, limits.memory)}
                                                step={128}
                                                value={values.memory}
                                                onChange={e => setFieldValue('memory', parseInt(e.target.value))}
                                            />
                                            <ErrorMessage name='memory' component='div' className='text-red-500 text-xs mt-1' />
                                        </div>

                                        <div className='bg-[#111] p-4 rounded-lg border border-white/5'>
                                            <div className='flex justify-between items-center mb-1'>
                                                <Label style={{ marginBottom: 0 }}>Disque (SSD/NVMe)</Label>
                                                <span className='text-brand font-bold'>{values.disk} MB</span>
                                            </div>
                                            <p className='text-xs text-white/30 mb-2'>Maximum: {Math.min(available.disk || 0, limits.disk)} MB</p>
                                            <Slider
                                                type='range'
                                                name='disk'
                                                min={128}
                                                max={Math.min(available.disk || 0, limits.disk)}
                                                step={256}
                                                value={values.disk}
                                                onChange={e => setFieldValue('disk', parseInt(e.target.value))}
                                            />
                                            <ErrorMessage name='disk' component='div' className='text-red-500 text-xs mt-1' />
                                        </div>

                                        <div className='bg-[#111] p-4 rounded-lg border border-white/5'>
                                            <div className='flex justify-between items-center mb-1'>
                                                <Label style={{ marginBottom: 0 }}>Bases de données</Label>
                                                <span className='text-brand font-bold'>{values.databases}</span>
                                            </div>
                                            <p className='text-xs text-white/30 mb-2'>Maximum: {Math.min(available.databases || 0, limits.databases)}</p>
                                            <Slider
                                                type='range'
                                                name='databases'
                                                min={0}
                                                max={Math.min(available.databases || 0, limits.databases)}
                                                step={1}
                                                value={values.databases}
                                                onChange={e => setFieldValue('databases', parseInt(e.target.value))}
                                            />
                                        </div>

                                        <div className='bg-[#111] p-4 rounded-lg border border-white/5'>
                                            <div className='flex justify-between items-center mb-1'>
                                                <Label style={{ marginBottom: 0 }}>Backups (Sauvegardes)</Label>
                                                <span className='text-brand font-bold'>{values.backups}</span>
                                            </div>
                                            <p className='text-xs text-white/30 mb-2'>Maximum: {Math.min(available.backups || 0, limits.backups)}</p>
                                            <Slider
                                                type='range'
                                                name='backups'
                                                min={0}
                                                max={Math.min(available.backups || 0, limits.backups)}
                                                step={1}
                                                value={values.backups}
                                                onChange={e => setFieldValue('backups', parseInt(e.target.value))}
                                            />
                                        </div>
                                    </div>

                                    <div className='mt-10'>
                                        <button
                                            type='submit'
                                            disabled={isSubmitting || (available.slots || 0) <= 0}
                                            className='w-full py-4 bg-brand hover:bg-brand-dark text-white font-black uppercase tracking-widest rounded-md transition-all shadow-xl shadow-brand/20 disabled:opacity-50 disabled:grayscale'
                                        >
                                            {isSubmitting ? 'Création en cours...' : 'Déployer maintenant'}
                                        </button>
                                        {!isValid && submitCount > 0 && (
                                            <p className='text-red-500 text-center text-sm mt-4 font-bold'>
                                                Veuillez corriger les erreurs ci-dessus avant de continuer.
                                            </p>
                                        )}
                                        {(available.slots || 0) <= 0 && (
                                            <p className='text-red-500 text-center text-sm mt-4 font-bold'>
                                                Vous n'avez plus de slots de serveur disponibles (Slots restants: {available.slots || 0}). Passez à la boutique !
                                            </p>
                                        )}
                                    </div>
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
