import { useEffect, useState } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import http from '@/api/http';
import useFlash from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';
import { Cpu, Database, Folder, Layers, Plus, Tag } from '@gravity-ui/icons';
import styled from 'styled-components';
import { useStoreActions, useStoreState } from '@/state/hooks';

const Card = styled.div`
    background: #1a1a1a;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    flex-direction: column;
    gap: 1rem;
    transition: all 0.2s ease-in-out;
    
    &:hover {
        border-color: rgba(var(--brand), 0.3);
        background: #222;
    }
`;

interface Product {
    id: number;
    name: string;
    description: string;
    type: 'cpu' | 'memory' | 'disk' | 'backups' | 'databases' | 'slots';
    amount: number;
    price: number;
}

const StoreContainer = () => {
    const { addFlash } = useFlash();
    const [loading, setLoading] = useState(true);
    const [products, setProducts] = useState<Product[]>([]);

    const balance = useStoreState((state) => state.user.data?.coins || 0);
    const updateUserData = useStoreActions((actions) => actions.user.updateUserData);

    const loadStoreData = () => {
        setLoading(true);
        http.get('/api/client/store')
            .then(({ data }) => {
                setProducts(data.products);
                updateUserData({ coins: Number(data.balance), rate: Number(data.rate) });
            })
            .catch((error) => {
                console.error(error);
                addFlash({ type: 'error', message: 'Impossible de charger la boutique.' });
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadStoreData();
    }, []);

    const onPurchase = (productId: number) => {
        http.post('/api/client/store/purchase', { product_id: productId })
            .then(({ data }) => {
                addFlash({ type: 'success', message: 'Achat effectué avec succès !' });
                updateUserData({ coins: Number(data.balance) });
                // Reload store data to show updated resources
                loadStoreData();
            })
            .catch((error) => {
                const message = error.response?.data?.error || 'Une erreur est survenue lors de l\'achat.';
                addFlash({ type: 'error', message });
            });
    };

    const getIcon = (type: string) => {
        switch (type) {
            case 'cpu': return <Cpu className='text-brand' width={24} height={24} />;
            case 'memory': return <Layers className='text-brand' width={24} height={24} />;
            case 'disk': return <Folder className='text-brand' width={24} height={24} />;
            case 'databases': return <Database className='text-brand' width={24} height={24} />;
            case 'slots': return <Plus className='text-brand' width={24} height={24} />;
            default: return <Tag className='text-brand' width={24} height={24} />;
        }
    };

    if (loading) return <Spinner centered />;

    return (
        <PageContentBlock title={'Boutique de Ressources'} showFlashKey={'store'}>
            <div className='flex flex-col gap-y-8'>
                <div className='flex flex-row justify-between items-center bg-brand/10 p-6 rounded-lg border border-brand/20 shadow-lg shadow-brand/5'>
                    <div>
                        <h1 className='text-2xl font-bold'>Boutique OpenPanel</h1>
                        <p className='text-white/60 mt-1'>Améliorez votre expérience en achetant des ressources supplémentaires.</p>
                    </div>
                    <div className='text-right'>
                        <p className='text-sm text-white/50 uppercase tracking-wider font-semibold'>Votre Solde</p>
                        <p className='text-3xl font-black text-brand'>{Number(balance).toFixed(2)} <span className='text-lg font-normal opacity-70'>Coins</span></p>
                    </div>
                </div>

                <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>
                    {products.map((product) => (
                        <Card key={product.id}>
                            <div className='flex items-center gap-x-4'>
                                <div className='p-3 bg-brand/10 rounded-lg'>
                                    {getIcon(product.type)}
                                </div>
                                <div className='flex flex-col'>
                                    <h2 className='text-lg font-bold'>{product.name}</h2>
                                    <p className='text-sm text-white/40'>{product.description}</p>
                                </div>
                            </div>

                            <div className='flex flex-col flex-1 justify-end mt-4'>
                                <div className='flex justify-between items-end mb-4'>
                                    <div>
                                        <p className='text-xs text-white/40 uppercase font-bold tracking-widest'>Prix</p>
                                        <p className='text-xl font-bold'>{product.price} Coins</p>
                                    </div>
                                    <div className='text-right'>
                                        <p className='text-xs text-white/40 uppercase font-bold tracking-widest'>Quantité</p>
                                        <p className='text-xl font-bold'>+{product.amount} {product.type === 'memory' || product.type === 'disk' ? 'MB' : product.type === 'cpu' ? '%' : ''}</p>
                                    </div>
                                </div>

                                <button
                                    onClick={() => onPurchase(product.id)}
                                    className='w-full py-3 bg-brand hover:bg-brand-dark text-white font-bold rounded-md transition-colors shadow-lg shadow-brand/20 disabled:opacity-50 disabled:cursor-not-allowed'
                                    disabled={balance < product.price}
                                >
                                    Acheter maintenant
                                </button>
                            </div>
                        </Card>
                    ))}
                </div>
            </div>
        </PageContentBlock>
    );
};

export default StoreContainer;
