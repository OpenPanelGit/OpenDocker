import { useEffect } from 'react';
import { useStoreActions } from '@/state/hooks';
import http from '@/api/http';

const StoreBackground = () => {
    const updateUserData = useStoreActions((actions) => actions.user.updateUserData);

    useEffect(() => {
        const interval = setInterval(() => {
            http.post('/api/client/store/afk')
                .then(({ data }) => {
                    if (data.success) {
                        updateUserData({ coins: Number(data.balance) });
                    }
                })
                .catch((error) => console.error('AFK gain failed:', error));
        }, 60000); // Pulse every minute

        return () => clearInterval(interval);
    }, []);

    return null;
};

export default StoreBackground;
