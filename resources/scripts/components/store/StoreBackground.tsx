import { useEffect, useRef } from 'react';
import { useStoreActions, useStoreState } from '@/state/hooks';
import http from '@/api/http';

const StoreBackground = () => {
    const updateUserData = useStoreActions((actions) => actions.user.updateUserData);
    const user = useStoreState((state) => state.user.data);
    const userRef = useRef(user);

    // Keep the ref updated with the latest user data (balance, rate, etc.)
    // but DON'T restart the interval effect below when user changes.
    useEffect(() => {
        userRef.current = user;
    }, [user]);

    useEffect(() => {
        if (!userRef.current) return;

        let accumulatedGain = 0;

        const sync = () => {
            if (accumulatedGain <= 0) return;

            const gainToSend = accumulatedGain;
            accumulatedGain = 0;

            http.post('/api/client/store/afk', { gain: gainToSend })
                .then(({ data }) => {
                    if (data.success) {
                        updateUserData({ coins: Number(data.balance) });
                    } else {
                        accumulatedGain += gainToSend;
                    }
                })
                .catch((error) => {
                    console.error('[Store] Sync failed:', error);
                    accumulatedGain += gainToSend;
                });
        };

        const tickInterval = setInterval(() => {
            const u = userRef.current;
            if (u && u.rate > 0) {
                const step = u.rate / 60;
                accumulatedGain += step;
                updateUserData({ coins: Number(u.coins) + step });
            }
        }, 1000);

        const syncInterval = setInterval(sync, 10000);

        return () => {
            clearInterval(tickInterval);
            clearInterval(syncInterval);
        };
    }, []); // Empty dependency array means this ONLY runs once on mount

    return null;
};

export default StoreBackground;
