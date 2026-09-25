import { SUBMISSION } from '../../data/landing';
import Reveal from '../Reveal';

export default function SubmissionCTA() {
  return (
    <section id="contact" className="kfm-cta">
      <div className="kfm-cta__bg">
        <img src={SUBMISSION.image} alt="" loading="lazy" />
      </div>

      <div className="kfm-container kfm-cta__content">
        <Reveal>
          <h2 className="kfm-display kfm-cta__title">{SUBMISSION.title}</h2>
          <p className="kfm-cta__lede">{SUBMISSION.lede}</p>
          <a href={SUBMISSION.cta.href} className="kfm-btn kfm-btn--primary">
            {SUBMISSION.cta.label}
          </a>
        </Reveal>
      </div>
    </section>
  );
}