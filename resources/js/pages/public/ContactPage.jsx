import { useEffect, useState } from 'react';
import Navbar from '../../components/public/Navbar';
import Footer from '../../components/public/Footer';
import Reveal from '../../components/Reveal';
import publicApi from '../../publicApi';

export default function ContactPage() {
  useEffect(() => { window.scrollTo(0, 0); }, []);

  const [form, setForm] = useState({ name: '', email: '', subject: '', message: '' });
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [reference, setReference] = useState('');
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  const handle = (e) => {
    const { name, value } = e.target;
    setForm((f) => ({ ...f, [name]: value }));
  };

  const submit = async (e) => {
    e.preventDefault();
    if (submitting) return;

    setSubmitting(true);
    setErrors({});
    setGlobalError('');

    const payload = {
      name: form.name,
      email: form.email,
      subject: form.subject,
      message: form.message,
      // Backend requires a category — send 'general' since the original
      // UX didn't have a category selector.
      category: 'general',
    };

    try {
      const { data } = await publicApi.post('/contact-messages', payload);
      setReference(data?.reference_number || '');
      setSubmitted(true);
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else if (err.response?.status === 429) {
        setGlobalError('Too many messages sent. Please try again later.');
      } else {
        setGlobalError(
          err.response?.data?.message ||
            'Something went wrong. Please try again.'
        );
      }
    } finally {
      setSubmitting(false);
    }
  };

  const fieldError = (key) => errors[key]?.[0];

  return (
    <>
      <Navbar />
      <main className="kfm-contact-page">

        {/* ---------- HERO ---------- */}
        <section className="kfm-contact-page__hero">
          <div className="kfm-contact-page__hero-bg" aria-hidden="true">
            {/* <img
              src="https://images.unsplash.com/photo-1519638831568-d9897f54ed69?auto=format&fit=crop&w=2000&q=80"
              alt="contact hero image"
            /> */}
          </div>

          <div className="kfm-container kfm-contact-page__hero-inner">
            <a href="/" className="kfm-back">Back home</a>
            <h1 className="kfm-contact-page__wordmark">Contact</h1>
          </div>
        </section>

        {/* ---------- SPLIT: COPY LEFT / FORM RIGHT ---------- */}
        <section className="kfm-contact-page__split">
          <div className="kfm-container">
            <div className="kfm-contact-page__grid">

              {/* LEFT — Copy + Info */}
              <Reveal>
                <div>
                  <h2 className="kfm-contact-page__copy-title">
                    We would love to hear from you
                  </h2>
                  <p className="kfm-contact-page__copy-text">
                    Do you want to book any of our artists for a show or a
                    project? Fill out the form or send us an email, and we&apos;ll
                    get back to you as soon as possible.
                  </p>

                  <div className="kfm-contact-page__info">
                    <div className="kfm-contact-page__info-item">
                      <span className="kfm-contact-page__info-label">
                        Studio &amp; Office
                      </span>
                      <span className="kfm-contact-page__info-value">
                        Hamthel Luxury Towels,<br />
                        Lekki, Lagos, Nigeria
                      </span>
                    </div>

                    <div className="kfm-contact-page__info-item">
                      <span className="kfm-contact-page__info-label">Email</span>
                      <a
                        href="mailto:Kayfactorymusic@gmail.com"
                        className="kfm-contact-page__info-value"
                      >
                        Kayfactorymusic@gmail.com
                      </a>
                    </div>
                  </div>
                </div>
              </Reveal>

              {/* RIGHT — Form */}
              <Reveal delay={120}>
                <div>
                  {submitted ? (
                    <div className="kfm-contact-page__success">
                      <h3 className="kfm-contact-page__success-title">
                        Thanks — we&apos;ll be in touch.
                      </h3>
                      {reference && (
                        <p className="kfm-contact-page__success-text">
                          Your reference number:{' '}
                          <strong style={{ color: 'var(--kfm-accent)' }}>
                            {reference}
                          </strong>
                        </p>
                      )}
                      <p className="kfm-contact-page__success-text">
                        We reply to every message. Expect to hear from us within
                        2–3 business days.
                      </p>
                    </div>
                  ) : (
                    <form className="kfm-contact-page__form" onSubmit={submit} noValidate>

                      {globalError && (
                        <div className="kfm-contact-page__error">
                          {globalError}
                        </div>
                      )}

                      <div className="kfm-contact-page__row">
                        <label className="kfm-contact-page__field">
                          <span className="kfm-contact-page__field-label">
                            Name
                          </span>
                          <input
                            type="text"
                            name="name"
                            required
                            value={form.name}
                            onChange={handle}
                            className={`kfm-contact-page__field-input ${fieldError('name') ? 'is-invalid' : ''}`}
                          />
                          {fieldError('name') && (
                            <div className="kfm-contact-page__field-error">
                              {fieldError('name')}
                            </div>
                          )}
                        </label>

                        <label className="kfm-contact-page__field">
                          <span className="kfm-contact-page__field-label">
                            Email <span>*</span>
                          </span>
                          <input
                            type="email"
                            name="email"
                            required
                            value={form.email}
                            onChange={handle}
                            className={`kfm-contact-page__field-input ${fieldError('email') ? 'is-invalid' : ''}`}
                          />
                          {fieldError('email') && (
                            <div className="kfm-contact-page__field-error">
                              {fieldError('email')}
                            </div>
                          )}
                        </label>
                      </div>

                      <label className="kfm-contact-page__field">
                        <span className="kfm-contact-page__field-label">
                          Subject
                        </span>
                        <input
                          type="text"
                          name="subject"
                          required
                          value={form.subject}
                          onChange={handle}
                          className={`kfm-contact-page__field-input ${fieldError('subject') ? 'is-invalid' : ''}`}
                        />
                        {fieldError('subject') && (
                          <div className="kfm-contact-page__field-error">
                            {fieldError('subject')}
                          </div>
                        )}
                      </label>

                      <label className="kfm-contact-page__field">
                        <span className="kfm-contact-page__field-label">
                          Leave us a message...
                        </span>
                        <textarea
                          name="message"
                          required
                          value={form.message}
                          onChange={handle}
                          className={`kfm-contact-page__field-textarea ${fieldError('message') ? 'is-invalid' : ''}`}
                        />
                        {fieldError('message') && (
                          <div className="kfm-contact-page__field-error">
                            {fieldError('message')}
                          </div>
                        )}
                      </label>

                      <button
                        type="submit"
                        className="kfm-contact-page__submit"
                        disabled={submitting}
                      >
                        {submitting ? 'Sending…' : 'Submit'}
                      </button>
                    </form>
                  )}
                </div>
              </Reveal>

            </div>

            {/* ---------- MAP ---------- */}
            <Reveal>
              <div className="kfm-contact-page__map">
                <div className="kfm-contact-page__map-card">
                  <h3 className="kfm-contact-page__map-card-title">
                    Kay Factory Music
                  </h3>
                  <p className="kfm-contact-page__map-card-address">
                    Hamthel Luxury Towels,<br />
                    Lekki, Lagos, Nigeria
                  </p>
                  <div className="kfm-contact-page__map-card-meta">
                    <i className="bi bi-geo-alt-fill" aria-hidden="true" />
                    <span>Studio &amp; Office</span>
                  </div>
                </div>

                <iframe
                  title="Kay Factory Music — Hamthel Luxury Towels, Lekki"
                  src="https://www.google.com/maps?q=Hamthel+Luxury+Towels+Lekki+Lagos&output=embed"
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                  allowFullScreen
                />
              </div>
            </Reveal>

          </div>
        </section>

      </main>
      <Footer />
    </>
  );
}