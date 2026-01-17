// Provides necessary information for components to function properly
// million-ignore
const OpenPanelProvider = ({ children }) => {
    return (
        <div
            data-openpanel-provider=''
            data-openpanel-version={import.meta.env.VITE_OPENPANEL_VERSION}
            data-openpanel-build={import.meta.env.VITE_OPENPANEL_BUILD_NUMBER}
            data-openpanel-commit-hash={import.meta.env.VITE_COMMIT_HASH}
            style={{
                display: 'contents',
            }}
        >
            {children}
        </div>
    );
};

export default OpenPanelProvider;
