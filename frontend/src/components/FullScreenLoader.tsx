import '../FullScreenLoader.css';

const FullScreenLoader = () => {
    return (
        <div className="fullscreen-loader">
            <div className="loader-card">
                <div className="logo">ALGM-Webs</div>
                <div className="spinner-modern"></div>
                <p className="loading-text">Cargando sistema...</p>
            </div>
        </div>
    );
};

export default FullScreenLoader;
