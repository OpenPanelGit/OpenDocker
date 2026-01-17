import { useEffect } from 'react';
import { useStoreActions, useStoreState } from '@/state/hooks';
import http from '@/api/http';

const StoreBackground = () => {
    const updateUserData = useStoreActions((actions) => actions.user.updateUserData);
    const user = useStoreState((state) => state.user.data);

    useEffect(() => {
        // 1. Per-second smooth visual bump
        const tickInterval = setInterval(() => {
            if (user && user.rate > 0) {
                // Increment local balance by rate/60
                const increment = user.rate / 60;
                updateUserData({ coins: user.coins + increment });
            }
        }, 1000);

        // 2. Per-minute server sync
        const syncInterval = setInterval(() => {
            http.post('/api/client/store/afk')
                .then(({ data }) => {
                    if (data.success) {
                        updateUserData({ coins: Number(data.balance), rate: Number(data.rate) });
                        if (data.gain) console.log(`[Store] Sync réussi ! Gain validé: ${data.gain}. Solde actuel: ${data.balance}`);
                    }
                })
                .catch((error) => console.error('AFK sync failed:', error));
        }, 60000);

        return () => {
            clearInterval(tickInterval);
            clearInterval(syncInterval);
        };
    }, [user?.rate, user?.coins]);

    return null;
};

export default StoreBackground;
