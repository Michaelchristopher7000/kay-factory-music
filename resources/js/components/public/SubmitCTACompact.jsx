import Reveal from "../Reveal";

export default function SubmitCTACompact() {
    return (
        <section className="kfm-cta-compact">
            <div className="kfm-container">
                <Reveal>
                    <div className="kfm-cta-compact__row">
                        <div className="kfm-cta-compact__body">
                            <div className="kfm-cta-compact__eyebrow">
                                Now accepting demos
                            </div>
                            <h2 className="kfm-cta-compact__title">
                                Your sound deserves a stage.
                            </h2>
                            <p className="kfm-cta-compact__lede">
                                Send us your music — we listen to every
                                submission that comes in.
                            </p>
                        </div>

                        <div className="kfm-cta-compact__actions">
                            <a
                                href="/submit-demo"
                                className="kfm-cta-compact__btn"
                            >
                                Submit Your Demo
                                <i className="bi bi-arrow-up-right" aria-hidden="true" />
                            </a>
                        </div>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}