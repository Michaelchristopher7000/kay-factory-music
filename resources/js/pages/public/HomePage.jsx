import { useEffect } from "react";
import Navbar from "../../components/public/Navbar";
import Hero from "../../components/public/Hero";
import GenreTicker from "../../components/public/GenreTicker";
import BrandStatement from "../../components/public/BrandStatement";
import EventFeature from "../../components/public/EventFeature";
import FeaturedVideo from '../../components/public/FeaturedVideo';
import RosterAndLatest from "../../components/public/Roster2026";
import SubmitCTACompact from "../../components/public/SubmitCTACompact";
import Footer from "../../components/public/Footer";

export default function HomePage() {
    useEffect(() => {
        window.scrollTo(0, 0);
        document.title = "Kay Factory Music — Where Sound Becomes Legacy";
    }, []);

    return (
        <>
            <Navbar />

            <main style={{ background: "#080808", overflow: "hidden" }}>
                <Hero />
                <GenreTicker />
                <BrandStatement />
                <FeaturedVideo />
                <EventFeature />
                <RosterAndLatest />
                <SubmitCTACompact />
            </main>

            <Footer />
        </>
    );
}