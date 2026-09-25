import { useEffect, useState } from 'react';
import Navbar from '../../components/public/Navbar';
import Footer from '../../components/public/Footer';
import Reveal from '../../components/Reveal';
import FileDrop from '../../components/public/FileDrop';
import publicApi from '../../publicApi';
import { SUBMIT_PAGE } from '../../data/landing';

const TALENT_CATEGORIES = [
  'Music Artist',
  'Singer',
  'Rapper',
  'Producer',
  'Songwriter',
  'DJ',
  'Dancer',
  'Other',
];

const emptyForm = {
  fullName: '',
  email: '',
  phone: '',
  location: '',
  talentCategory: '',
  bio: '',
  message: '',
  instagram: '',
  tiktok: '',
  youtube: '',
  twitter: '',
  facebook: '',
  website: '',
  consent: false,
};

export default function SubmitDemoPage() {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  const [form, setForm] = useState(emptyForm);
  const [audio, setAudio] = useState(null);
  const [video, setVideo] = useState(null);
  const [image, setImage] = useState(null);

  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [reference, setReference] = useState('');
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  const handle = (e) => {
    const { name, value, type, checked } = e.target;

    setForm((f) => ({
      ...f,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const submit = async (e) => {
    e.preventDefault();

    if (submitting) return;

    setSubmitting(true);
    setErrors({});
    setGlobalError('');

    const fd = new FormData();

    fd.append('full_name', form.fullName);
    fd.append('email', form.email);
    fd.append('phone', form.phone);

    if (form.location) {
      fd.append('location', form.location);
    }

    fd.append('talent_category', form.talentCategory);

    if (form.bio) {
      fd.append('bio', form.bio);
    }

    if (form.message) {
      fd.append('message', form.message);
    }

    const socials = {
      instagram: form.instagram,
      tiktok: form.tiktok,
      youtube: form.youtube,
      twitter: form.twitter,
      facebook: form.facebook,
      website: form.website,
    };

    Object.entries(socials).forEach(([key, value]) => {
      if (value) {
        fd.append(`social_links[${key}]`, value);
      }
    });

    if (audio) {
      fd.append('audio', audio);
    }

    if (video) {
      fd.append('video', video);
    }

    if (image) {
      fd.append('image', image);
    }

    fd.append('consent', form.consent ? '1' : '0');

    try {
      const { data } = await publicApi.post(
        '/talent-submissions',
        fd,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );

      setReference(data?.reference_number || '');
      setSubmitted(true);
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else if (err.response?.status === 429) {
        setGlobalError(
          'Too many submissions from this device. Please try again later.'
        );
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

      <main>
        {/* ============================================================
            HERO — NO IMAGE
           ============================================================ */}
        <section className="kfm-page-hero kfm-page-hero--clean">
          <div className="kfm-container kfm-page-hero__content">
            <a href="/" className="kfm-back">
              Back home
            </a>

            <div className="kfm-page-hero__eyebrow">
              {SUBMIT_PAGE.hero.eyebrow}
            </div>

            <h1 className="kfm-display kfm-page-hero__title">
              {SUBMIT_PAGE.hero.title}
            </h1>

            <p className="kfm-page-hero__lede">
              {SUBMIT_PAGE.hero.lede}
            </p>
          </div>
        </section>

        {/* ============================================================
            GUIDELINES
           ============================================================ */}
        <section className="kfm-section">
          <div className="kfm-container">
            <div className="kfm-submit-guides">

              <Reveal>
                <div className="kfm-guide">
                  <div className="kfm-eyebrow">
                    Requirements
                  </div>

                  <h3 className="kfm-guide__title">
                    {SUBMIT_PAGE.lookFor.title}
                  </h3>

                  <ul className="kfm-guide__list">
                    {SUBMIT_PAGE.lookFor.items.map((item) => (
                      <li key={item}>
                        {item}
                      </li>
                    ))}
                  </ul>
                </div>
              </Reveal>

              <Reveal delay={120}>
                <div className="kfm-guide">
                  <div className="kfm-eyebrow">
                    Before You Send
                  </div>

                  <h3 className="kfm-guide__title">
                    {SUBMIT_PAGE.guidelines.title}
                  </h3>

                  <ul className="kfm-guide__list">
                    {SUBMIT_PAGE.guidelines.items.map((item) => (
                      <li key={item}>
                        {item}
                      </li>
                    ))}
                  </ul>
                </div>
              </Reveal>

            </div>
          </div>
        </section>

        {/* ============================================================
            FORM
           ============================================================ */}
        <section
          className="kfm-section"
          style={{ background: 'var(--kfm-ink)' }}
        >
          <div className="kfm-container">

            <div className="kfm-submit-form-wrap">

              {submitted ? (

                /* ======================================================
                   SUCCESS
                   ====================================================== */
                <Reveal>
                  <div className="kfm-form-success">

                    <div className="kfm-eyebrow">
                      Submission Received
                    </div>

                    <h3 className="kfm-display kfm-form-success__title">
                      Thank you — we&apos;ve got it.
                    </h3>

                    {reference && (
                      <p className="kfm-form-success__text">
                        Your reference number:{' '}

                        <strong
                          style={{
                            color: 'var(--kfm-accent)',
                          }}
                        >
                          {reference}
                        </strong>
                      </p>
                    )}

                    <p className="kfm-form-success__text">
                      We listen to every submission. Expect to hear back
                      within 4–6 weeks. If it&apos;s a fit, we&apos;ll reach
                      out to set up a conversation.
                    </p>

                    <a
                      href="/"
                      className="kfm-btn kfm-btn--ghost"
                    >
                      Back to Home
                    </a>

                  </div>
                </Reveal>

              ) : (

                /* ======================================================
                   FORM
                   ====================================================== */
                <Reveal>

                  <div className="kfm-eyebrow">
                    Submit Your Music
                  </div>

                  <h2 className="kfm-display kfm-submit-form__title">
                    Tell us who you are.
                  </h2>

                  {globalError && (
                    <div className="kfm-form__alert">
                      {globalError}
                    </div>
                  )}

                  <form
                    className="kfm-form kfm-form--wide"
                    onSubmit={submit}
                    noValidate
                  >

                    {/* ==================================================
                        PERSONAL INFORMATION
                       ================================================== */}
                    <div className="kfm-form__row">

                      <label className="kfm-form__label">
                        <span>Full Name *</span>

                        <input
                          type="text"
                          name="fullName"
                          required
                          value={form.fullName}
                          onChange={handle}
                          className={`kfm-form__input ${
                            fieldError('full_name')
                              ? 'is-invalid'
                              : ''
                          }`}
                        />

                        {fieldError('full_name') && (
                          <div className="kfm-form__error">
                            {fieldError('full_name')}
                          </div>
                        )}
                      </label>

                      <label className="kfm-form__label">
                        <span>Email *</span>

                        <input
                          type="email"
                          name="email"
                          required
                          value={form.email}
                          onChange={handle}
                          className={`kfm-form__input ${
                            fieldError('email')
                              ? 'is-invalid'
                              : ''
                          }`}
                        />

                        {fieldError('email') && (
                          <div className="kfm-form__error">
                            {fieldError('email')}
                          </div>
                        )}
                      </label>

                    </div>

                    <div className="kfm-form__row">

                      <label className="kfm-form__label">
                        <span>Phone *</span>

                        <input
                          type="tel"
                          name="phone"
                          required
                          value={form.phone}
                          onChange={handle}
                          className={`kfm-form__input ${
                            fieldError('phone')
                              ? 'is-invalid'
                              : ''
                          }`}
                        />

                        {fieldError('phone') && (
                          <div className="kfm-form__error">
                            {fieldError('phone')}
                          </div>
                        )}
                      </label>

                      <label className="kfm-form__label">
                        <span>Location</span>

                        <input
                          type="text"
                          name="location"
                          placeholder="City, Country"
                          value={form.location}
                          onChange={handle}
                          className="kfm-form__input"
                        />
                      </label>

                    </div>

                    {/* ==================================================
                        TALENT
                       ================================================== */}
                    <div className="kfm-form__row">

                      <label className="kfm-form__label">
                        <span>Talent Category *</span>

                        <select
                          name="talentCategory"
                          required
                          value={form.talentCategory}
                          onChange={handle}
                          className={`kfm-form__input ${
                            fieldError('talent_category')
                              ? 'is-invalid'
                              : ''
                          }`}
                        >
                          <option value="">
                            — Select —
                          </option>

                          {TALENT_CATEGORIES.map((category) => (
                            <option
                              key={category}
                              value={category}
                            >
                              {category}
                            </option>
                          ))}
                        </select>

                        {fieldError('talent_category') && (
                          <div className="kfm-form__error">
                            {fieldError('talent_category')}
                          </div>
                        )}
                      </label>

                    </div>

                    {/* ==================================================
                        BIO
                       ================================================== */}
                    <label className="kfm-form__label">
                      <span>Short Bio</span>

                      <textarea
                        name="bio"
                        rows={4}
                        placeholder="Who you are, where you're based, what you're building."
                        value={form.bio}
                        onChange={handle}
                        className="kfm-form__textarea"
                      />
                    </label>

                    {/* ==================================================
                        MESSAGE
                       ================================================== */}
                    <label className="kfm-form__label">
                      <span>Message / Description</span>

                      <textarea
                        name="message"
                        rows={3}
                        placeholder="Anything else we should know?"
                        value={form.message}
                        onChange={handle}
                        className="kfm-form__textarea"
                      />
                    </label>

                    {/* ==================================================
                        SOCIAL LINKS
                       ================================================== */}
                    <div className="kfm-form__row">

                      <label className="kfm-form__label">
                        <span>Instagram</span>

                        <input
                          type="url"
                          name="instagram"
                          placeholder="https://instagram.com/..."
                          value={form.instagram}
                          onChange={handle}
                          className="kfm-form__input"
                        />
                      </label>

                      <label className="kfm-form__label">
                        <span>TikTok</span>

                        <input
                          type="url"
                          name="tiktok"
                          placeholder="https://tiktok.com/@..."
                          value={form.tiktok}
                          onChange={handle}
                          className="kfm-form__input"
                        />
                      </label>

                    </div>

                    <div className="kfm-form__row">

                      <label className="kfm-form__label">
                        <span>YouTube</span>

                        <input
                          type="url"
                          name="youtube"
                          placeholder="https://youtube.com/..."
                          value={form.youtube}
                          onChange={handle}
                          className="kfm-form__input"
                        />
                      </label>

                      <label className="kfm-form__label">
                        <span>X / Twitter</span>

                        <input
                          type="url"
                          name="twitter"
                          placeholder="https://x.com/..."
                          value={form.twitter}
                          onChange={handle}
                          className="kfm-form__input"
                        />
                      </label>

                    </div>

                    <div className="kfm-form__row">

                      <label className="kfm-form__label">
                        <span>Facebook</span>

                        <input
                          type="url"
                          name="facebook"
                          placeholder="https://facebook.com/..."
                          value={form.facebook}
                          onChange={handle}
                          className="kfm-form__input"
                        />
                      </label>

                      <label className="kfm-form__label">
                        <span>Website</span>

                        <input
                          type="url"
                          name="website"
                          placeholder="https://..."
                          value={form.website}
                          onChange={handle}
                          className="kfm-form__input"
                        />
                      </label>

                    </div>

                    {/* ==================================================
                        MEDIA UPLOADS
                       ================================================== */}
                    <div className="kfm-form__uploads">

                      <div className="kfm-form__uploads-note">
                        Upload at least one of:{' '}
                        <strong>audio</strong>,{' '}
                        <strong>video</strong>, or{' '}
                        <strong>image</strong>.
                      </div>

                      <FileDrop
                        label="Audio Demo"
                        accept="audio/mpeg,audio/mp3,audio/wav,audio/mp4,audio/m4a,audio/aac,audio/ogg,audio/webm"
                        maxSize={50 * 1024 * 1024}
                        file={audio}
                        onFile={setAudio}
                        hint="MP3, WAV, M4A, AAC, OGG · Max 50 MB"
                      />

                      <FileDrop
                        label="Music Video"
                        accept="video/mp4,video/quicktime,video/webm"
                        maxSize={500 * 1024 * 1024}
                        file={video}
                        onFile={setVideo}
                        hint="MP4, MOV, WebM · Max 500 MB"
                      />

                      <FileDrop
                        label="Artist Photo"
                        accept="image/jpeg,image/png,image/webp"
                        maxSize={10 * 1024 * 1024}
                        file={image}
                        onFile={setImage}
                        hint="JPG, PNG, WebP · Max 10 MB"
                      />

                      {fieldError('audio') && (
                        <div className="kfm-form__error">
                          {fieldError('audio')}
                        </div>
                      )}

                    </div>

                    {/* ==================================================
                        CONSENT
                       ================================================== */}
                    <label className="kfm-form__consent">

                      <input
                        type="checkbox"
                        name="consent"
                        checked={form.consent}
                        onChange={handle}
                        className={`form-check-input ${
                          fieldError('consent')
                            ? 'is-invalid'
                            : ''
                        }`}
                      />

                      <span>
                        I confirm that the information and media submitted
                        are mine or that I have the necessary rights to
                        submit them, and I agree that Kay Factory Music may
                        review this submission.
                      </span>

                    </label>

                    {fieldError('consent') && (
                      <div className="kfm-form__error">
                        {fieldError('consent')}
                      </div>
                    )}

                    {/* ==================================================
                        SUBMIT
                       ================================================== */}
                    <button
                      type="submit"
                      className="kfm-btn kfm-btn--primary"
                      disabled={submitting}
                    >
                      {submitting
                        ? 'Uploading…'
                        : 'Submit Demo'}
                    </button>

                  </form>

                </Reveal>
              )}

            </div>
          </div>
        </section>
      </main>

      <Footer />
    </>
  );
}