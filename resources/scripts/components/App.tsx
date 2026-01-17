// Because of how react-router, react lazy, and signals work with each other
// the only way to prevent mismatching and weird errors is to import the lib
// in the root first. The github issue for this is still open. Stupid.
// https://github.com/preactjs/signals/issues/414
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';
import '@/assets/tailwind.css';
import '@preact/signals-react';
import { StoreProvider } from 'easy-peasy';
import { lazy } from 'react';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { Toaster } from 'sonner';

import AuthenticatedRoute from '@/components/elements/AuthenticatedRoute';
import { NotFound } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';

import { store } from '@/state';
import { ServerContext } from '@/state/server';
import { SiteSettings } from '@/state/settings';

import OpenPanelProvider from './OpenPanelProvider';
import StoreBackground from '@/components/store/StoreBackground';

const DashboardRouter = lazy(() => import('@/routers/DashboardRouter'));
const ServerRouter = lazy(() => import('@/routers/ServerRouter'));
const AuthenticationRouter = lazy(() => import('@/routers/AuthenticationRouter'));

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
    OpenPanelUser?: {
        uuid: string;
        username: string;
        email: string;

        root_admin: boolean;
        use_totp: boolean;
        language: string;
        updated_at: string;
        created_at: string;
        coins: number;
        rate: number;
        bought_cpu: number;
        bought_memory: number;
        bought_disk: number;
        bought_slots: number;
        bought_databases: number;
        bought_backups: number;
    };
}

const App = () => {
    const { OpenPanelUser, SiteConfiguration } = window as ExtendedWindow;
    if (OpenPanelUser && !store.getState().user.data) {
        store.getActions().user.setUserData({
            uuid: OpenPanelUser.uuid,
            username: OpenPanelUser.username,
            email: OpenPanelUser.email,
            language: OpenPanelUser.language,
            rootAdmin: OpenPanelUser.root_admin,
            useTotp: OpenPanelUser.use_totp,
            createdAt: new Date(OpenPanelUser.created_at),
            updatedAt: new Date(OpenPanelUser.updated_at),
            coins: Number(OpenPanelUser.coins),
            rate: Number(OpenPanelUser.rate),
            boughtCpu: OpenPanelUser.bought_cpu,
            boughtMemory: OpenPanelUser.bought_memory,
            boughtDisk: OpenPanelUser.bought_disk,
            boughtSlots: OpenPanelUser.bought_slots,
            boughtDatabases: OpenPanelUser.bought_databases,
            boughtBackups: OpenPanelUser.bought_backups,
        });
    }

    if (!store.getState().settings.data) {
        store.getActions().settings.setSettings(SiteConfiguration!);
    }

    return (
        <>
            <GlobalStylesheet />
            <StoreProvider store={store}>
                <OpenPanelProvider>
                    <StoreBackground />
                    <div
                        data-openpanel-routerwrap=''
                        className='relative w-full h-full flex flex-row p-2 overflow-hidden rounded-lg'
                    >
                        <Toaster
                            theme='dark'
                            toastOptions={{
                                unstyled: true,
                                classNames: {
                                    toast: 'p-4 bg-[#ffffff09] border border-[#ffffff12] rounded-2xl shadow-lg backdrop-blur-2xl flex items-center w-full gap-2',
                                },
                            }}
                        />
                        <BrowserRouter>
                            <Routes>
                                <Route
                                    path='/auth/*'
                                    element={
                                        <Spinner.Suspense>
                                            <AuthenticationRouter />
                                        </Spinner.Suspense>
                                    }
                                />

                                <Route
                                    path='/server/:id/*'
                                    element={
                                        <AuthenticatedRoute>
                                            <Spinner.Suspense>
                                                <ServerContext.Provider>
                                                    <ServerRouter />
                                                </ServerContext.Provider>
                                            </Spinner.Suspense>
                                        </AuthenticatedRoute>
                                    }
                                />

                                <Route
                                    path='/*'
                                    element={
                                        <AuthenticatedRoute>
                                            <Spinner.Suspense>
                                                <DashboardRouter />
                                            </Spinner.Suspense>
                                        </AuthenticatedRoute>
                                    }
                                />

                                <Route path='*' element={<NotFound />} />
                            </Routes>
                        </BrowserRouter>
                    </div>
                </OpenPanelProvider>
            </StoreProvider>
        </>
    );
};

export default App;
