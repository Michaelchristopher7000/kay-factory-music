import Reveal from "../Reveal";

export default function BrandStatement() {
    return (
        <section className="kfm-brand">
            <div className="kfm-container kfm-brand__inner">
                <Reveal>
                    <h2 className="kfm-brand__headline" aria-hidden="true">
                        ABOUT
                    </h2>
                </Reveal>

                <Reveal delay={100}>
                    <h3 className="kfm-brand__subhead">WHO WE ARE</h3>
                </Reveal>

                <Reveal delay={180}>
                    <p className="kfm-brand__copy">
                        Kay Factory Music is an independent record label built
                        around discovery, development, and the long game. We
                        work with artists at every stage — from first studio
                        session to global release — and we build records that
                        carry weight, craft, and culture.
                    </p>
                </Reveal>
            </div>
        </section>
    );
}