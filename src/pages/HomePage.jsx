import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { getAllEvents } from '@/services/eventService';
import { Music, Users, TrendingUp, Utensils, Wine, Calendar, Coffee, Heart, Building2, Gem, Globe, MapPin, ArrowRight, Mail } from 'lucide-react';
import { motion } from 'framer-motion';
import BrandLogo from '@/components/BrandLogo';
import { usePageT } from '@/hooks/useSiteLabel';
import { COMPANY_NAME, CONTACT_EMAIL, DEFAULT_LOGO_URL } from '@/constants/branding';

function HomePage() {
  const th = usePageT('home');
  const [upcomingEvents, setUpcomingEvents] = useState([]);
  useEffect(() => {
    let isMounted = true;
    const initData = async () => {
      try {
        const events = await getAllEvents();
        if (isMounted) {
          setUpcomingEvents(Array.isArray(events) ? events.slice(0, 3) : []);
        }
      } catch (error) {
        console.error('HomePage: Failed to load events', error);
        if (isMounted) setUpcomingEvents([]);
      }
    };
    initData();
    return () => {
      isMounted = false;
    };
  }, []);

  const pillars = [
    { icon: <Music className="w-12 h-12" />, title: 'Live', description: 'Entertainment, dining, music, and the energy of urban Kigali.' },
    { icon: <Users className="w-12 h-12" />, title: 'Connect', description: 'Networking, community, and friendships across the expat world.' },
    { icon: <TrendingUp className="w-12 h-12" />, title: 'Thrive', description: 'Wellness, lifestyle, opportunity, and elevated experiences.' },
  ];
  const spaces = [
    { icon: <Utensils className="w-10 h-10" />, title: 'Restaurant', description: 'Fine dining and everyday tables with Rwandan hospitality.' },
    { icon: <Wine className="w-10 h-10" />, title: 'Lounge', description: 'A refined space to meet, linger, and belong.' },
    { icon: <Calendar className="w-10 h-10" />, title: 'Event Space', description: 'Club nights, gatherings, and private celebrations.' },
    { icon: <Coffee className="w-10 h-10" />, title: 'Cafe', description: 'Crafted coffee, tea, juices, and our signature complimentary bite.' },
  ];
  const values = [
    { icon: <Heart className="w-9 h-9" />, name: 'Hospitality' },
    { icon: <Building2 className="w-9 h-9" />, name: 'Urban Culture' },
    { icon: <Gem className="w-9 h-9" />, name: 'Premium Lifestyle' },
    { icon: <Globe className="w-9 h-9" />, name: 'International Community' },
    { icon: <MapPin className="w-9 h-9" />, name: 'Experiential Destination' },
  ];

  return (
    <>
      <Helmet>
        <title>{COMPANY_NAME} | Expats Club</title>
        <meta name="description" content={`${COMPANY_NAME} — a destination, a community, an experience. Live. Connect. Thrive. in Kigali, Rwanda.`} />
      </Helmet>

      <section className="relative min-h-screen flex flex-col items-center justify-center overflow-hidden py-24 bg-black">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(197,160,89,0.18),_transparent_60%)]" />
        <div className="relative z-10 max-w-5xl mx-auto px-4 text-center">
          <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8 }}>
            <BrandLogo
              alt={COMPANY_NAME}
              className="h-48 md:h-72 w-auto object-contain mb-8 mx-auto drop-shadow-2xl"
              variant="onDark"
              preferSystemLogo={false}
              src={DEFAULT_LOGO_URL}
            />
            <p className="text-[#C5A059] text-xs md:text-sm tracking-[0.45em] uppercase mb-4">Expats Club · Experience Rwanda</p>
            <h1 className="text-3xl md:text-5xl lg:text-6xl font-bold text-white mb-4 tracking-wide">
              {th('hero_title_line1', 'A destination. A community. An')}{' '}
              <span className="text-[#C5A059]">{th('hero_title_highlight', 'experience')}.</span>
            </h1>
            <p className="text-lg md:text-xl text-white/80 font-light max-w-3xl mx-auto">
              {th('hero_subtitle', 'Welcome 2 Kigali Expats Club — live, connect, and thrive in Rwanda.')}
            </p>
            <p className="mt-6 text-white tracking-[0.35em] uppercase text-sm">Live. Connect. Thrive.</p>
          </motion.div>
          <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 flex-wrap">
            <Link to="/register-now">
              <Button className="bg-[#C5A059] hover:bg-[#b08d45] text-black h-14 px-8 text-lg font-bold rounded-full">
                {th('cta_primary', 'Join the Club')} <ArrowRight className="ml-2 w-5 h-5" />
              </Button>
            </Link>
            <Link to="/events">
              <Button variant="outline" className="h-14 px-8 text-lg font-bold rounded-full border-[#C5A059] text-[#C5A059]">
                <Calendar className="w-5 h-5 mr-2" /> Events
              </Button>
            </Link>
            <Link to="/menu">
              <Button variant="outline" className="h-14 px-8 text-lg font-bold rounded-full border-white/30 text-white">
                <Coffee className="w-5 h-5 mr-2" /> Cafe Menu
              </Button>
            </Link>
          </div>
        </div>
      </section>

      <section className="py-20 bg-black text-white">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-14">
            <h2 className="text-4xl font-bold text-[#C5A059] mb-3">{th('why_heading', 'Live. Connect. Thrive.')}</h2>
            <p className="text-lg text-gray-300">{th('why_subheading', 'The pillars of the Welcome 2 Kigali experience')}</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {pillars.map((p) => (
              <div key={p.title} className="border border-[#C5A059]/30 rounded-xl p-8 text-center bg-white/5">
                <div className="text-[#C5A059] mb-4 flex justify-center">{p.icon}</div>
                <h3 className="text-2xl tracking-widest uppercase text-white mb-3">{p.title}</h3>
                <p className="text-gray-300">{p.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-20 bg-[#F7F1E8]">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-4xl font-bold text-black mb-4">{th('services_heading', 'Restaurant. Lounge. Events. Cafe.')}</h2>
            <p className="text-xl text-gray-600">{th('services_subheading', 'A premium hospitality destination for the international community in Kigali')}</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {spaces.map((s) => (
              <div key={s.title} className="bg-white rounded-xl p-8 border border-[#C5A059]/20 shadow-sm">
                <div className="text-[#C5A059] mb-4">{s.icon}</div>
                <h3 className="text-xl font-semibold text-black mb-2">{s.title}</h3>
                <p className="text-gray-600 text-sm">{s.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-4xl font-bold text-black mb-4">{th('industries_heading', 'What we stand for')}</h2>
            <p className="text-xl text-gray-600">{th('industries_subheading', 'Hospitality, urban culture, and a place to belong')}</p>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-6">
            {values.map((v) => (
              <div key={v.name} className="text-center p-4">
                <div className="text-[#C5A059] mb-3 flex justify-center">{v.icon}</div>
                <h3 className="text-sm font-semibold uppercase tracking-wide text-black">{v.name}</h3>
              </div>
            ))}
          </div>
        </div>
      </section>

      {upcomingEvents.length > 0 && (
        <section className="py-16 bg-[#F7F1E8]">
          <div className="max-w-7xl mx-auto px-4">
            <div className="text-center mb-12">
              <h2 className="text-4xl font-bold text-black mb-4">{th('upcoming_events', 'Upcoming Events')}</h2>
              <p className="text-xl text-gray-600">{th('upcoming_events_subtitle', 'Gatherings at the club')}</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {upcomingEvents.map((evt) => (
                <div key={evt?.id || evt?.title} className="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                  <div className="h-48 bg-gray-200">
                    <img src={evt?.image_url || 'https://via.placeholder.com/400x200'} alt={evt?.title || 'Event'} className="w-full h-full object-cover" />
                  </div>
                  <div className="p-6">
                    <h3 className="text-xl font-bold text-black mb-2">{evt?.title}</h3>
                    <p className="text-gray-600 line-clamp-2 mb-4">{evt?.description || th('details_soon', 'Details coming soon.')}</p>
                    <Link to="/events">
                      <Button className="w-full bg-[#C5A059] text-black font-bold hover:bg-[#b08d45]">{th('view_details', 'View Details')}</Button>
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      <section className="py-16 bg-black text-white text-center">
        <div className="max-w-4xl mx-auto px-4">
          <h2 className="text-4xl font-bold text-[#C5A059] mb-4">{th('testimonials_heading', 'Cafe & Restaurant')}</h2>
          <p className="text-xl text-gray-300 mb-8">{th('testimonials_subheading', 'Crafted beverages and food — dine in or take away')}</p>
          <Link to="/menu">
            <Button className="bg-[#C5A059] text-black font-bold h-14 px-8 rounded-full">
              <Coffee className="w-5 h-5 mr-2" /> View the menu
            </Button>
          </Link>
        </div>
      </section>

      <section className="py-16 bg-[#C5A059] text-center">
        <div className="max-w-4xl mx-auto px-4">
          <h2 className="text-4xl font-bold text-black mb-6">{th('cta_heading', 'Experience Rwanda. Belong in Kigali.')}</h2>
          <p className="text-xl text-black/80 mb-8">{th('cta_text', 'Register for membership, join our events, or visit us at the club.')}</p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/register-now">
              <Button className="bg-black text-[#C5A059] h-14 px-8 text-lg font-semibold">Register</Button>
            </Link>
            <a href={`mailto:${CONTACT_EMAIL}`}>
              <Button className="bg-white text-black h-14 px-8 text-lg font-semibold">
                <Mail className="w-5 h-5 mr-2" /> Email Us
              </Button>
            </a>
          </div>
        </div>
      </section>
    </>
  );
}

export default HomePage;
