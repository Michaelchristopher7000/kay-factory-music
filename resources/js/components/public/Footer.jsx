import { FOOTER } from "../../data/landing";

export default function Footer() {
    return (
        <footer className="kfm-footer">
            <div className="kfm-container">
                <div className="kfm-footer__top">
                    <div>
                        <img
                            src="/images/kfm-logo-white.png"
                            alt="Kay Factory Music"
                            className="kfm-footer__logo"
                        />
                        <p className="kfm-footer__tagline">{FOOTER.tagline}</p>
                    </div>

                    {FOOTER.columns.map((col) => (
                        <div key={col.title}>
                            <h4 className="kfm-footer__col-title">
                                {col.title}
                            </h4>
                            <ul className="kfm-footer__links">
                                {col.links.map((link) => (
                                    <li key={link.label}>
                                        <a
                                            href={link.href}
                                            className="kfm-footer__link"
                                        >
                                            {link.icon && (
                                                <i
                                                    className={`bi ${link.icon}`}
                                                ></i>
                                            )}
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div className="kfm-footer__bottom">
                    <p className="kfm-footer__copyright">{FOOTER.copyright}</p>
                    <div className="kfm-footer__social">
                        <a
                            href="https://www.instagram.com/kayfactorymusic?stkn=MWdmdXZnaWVpaDluYQ=="
                            aria-label="Instagram"
                        >
                            <i className="bi bi-instagram"></i>
                        </a>
                        <a
                            href="https://x.com/kayfactorymusic?s=11"
                            aria-label="X"
                        >
                            <i className="bi bi-twitter-x"></i>
                        </a>
                        <a
                            href="https://www.tiktok.com/@kayfactorymusic?_r=1&_t=ZS-9A0WjIQNO3G"
                            aria-label="TikTok"
                        >
                            <i className="bi bi-tiktok"></i>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    );
}
