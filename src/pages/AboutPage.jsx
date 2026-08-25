import React from 'react';
import { Helmet } from 'react-helmet';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Heart, Globe2, UtensilsCrossed, Building2, Gem, Users, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';
import MembersSection from '@/components/MembersSection';
import { COMPANY_NAME, DEFAULT_LOGO_URL } from '@/constants/branding';

function AboutPage() {
  const pillars = [
    { icon: UtensilsCrossed, title: 'Hospitality', desc: 'Warm, welcoming experiences rooted in world-class service and authentic Rwandan hospitality.' },
    { icon: Building2, title: 'Urban Culture', desc: 'A vibrant fusion of modern city life, creativity, music, art, and contemporary African culture.' },
    { icon: Gem, title: 'Premium Lifestyle', desc: 'Elevated experiences curated for those who appreciate quality, comfort, and exclusivity.' },
    { icon: Users, title: 'International Community', desc: 'A diverse community of global minds coming together, sharing, growing, and belonging.' },
    { icon: MapPin, title: 'Experiential Destination', desc: 'Positioning Kigali as a must-experience destination through immersive, memorable experiences.' },
  ];

  return (
    <>
      <Helmet>
        <title>About {COMPANY_NAME}</title>
        <meta name="description" content="Welcome 2 Kigali Expats Club is more than a logo. It is the visual identity of a destination experience in Kigali, Rwanda." />
      </Helmet>

      <section className="relative py-24 bg-black text-white overflow-hidden">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(197,160,89,0.16),_transparent_65%)]" />
        <div className="relative max-w-7xl mx-auto px-4 text-center">
          <p className="text-[#C5A059] text-xs tracking-[0.4em] uppercase mb-4">Welcome 2 Kigali · Expats Club</p>
          <motion.h1 initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="text-4xl md:text-6xl font-bold mb-6">
            More than a logo. A destination experience.
          </motion.h1>
          <motion.p initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="text-xl md:text-2xl text-gray-200 max-w-3xl mx-auto font-light">
            A place where people arrive, connect, discover Rwanda, experience culture, build relationships, and create memories.
          </motion.p>
          <p className="mt-8 text-[#C5A059] tracking-[0.35em] uppercase text-sm">Live. Connect. Thrive.</p>
        </div>
      </section>

      <section className="py-16 bg-[#F7F1E8]">
        <div className="max-w-7xl mx-auto px-4">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-3xl font-bold text-black mb-6">Our Mission</h2>
              <p className="text-lg text-gray-600 mb-8 leading-relaxed">
                To welcome the international community into Kigali with world-class hospitality, authentic Rwandan culture, and a club where people live, connect, and thrive.
              </p>
              <div className="grid grid-cols-2 gap-6">
                <div className="flex items-start gap-3">
                  <div className="bg-black p-2 rounded-lg"><Heart className="w-6 h-6 text-[#C5A059]" /></div>
                  <div>
                    <h3 className="font-semibold text-gray-900">Hospitality</h3>
                    <p className="text-sm text-gray-500">Warm, world-class service</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <div className="bg-black p-2 rounded-lg"><Globe2 className="w-6 h-6 text-[#C5A059]" /></div>
                  <div>
                    <h3 className="font-semibold text-gray-900">Community</h3>
                    <p className="text-sm text-gray-500">International, membership-oriented</p>
                  </div>
                </div>
              </div>
            </div>
            <div className="relative rounded-2xl overflow-hidden bg-black flex items-center justify-center p-8">
              <img src={DEFAULT_LOGO_URL} alt={COMPANY_NAME} className="w-full max-h-96 object-contain" />
            </div>
          </div>
        </div>
      </section>

      <MembersSection />

      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 text-center">
          <h2 className="text-3xl font-bold text-black mb-12">The identity combines</h2>
          <div className="grid md:grid-cols-3 lg:grid-cols-5 gap-6">
            {pillars.map((val) => (
              <div key={val.title} className="p-6 bg-[#F7F1E8] rounded-xl hover:shadow-lg transition-shadow">
                <val.icon className="w-10 h-10 text-[#C5A059] mx-auto mb-4" />
                <h3 className="text-lg font-bold text-black mb-2">{val.title}</h3>
                <p className="text-gray-600 text-sm">{val.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-16 bg-black text-white text-center">
        <div className="max-w-4xl mx-auto px-4">
          <h2 className="text-3xl font-bold mb-6">Ready to belong in Kigali?</h2>
          <p className="text-xl mb-8 text-gray-300">Join the club, come to an event, or visit the cafe.</p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/register-now">
              <Button className="bg-[#C5A059] text-black font-bold text-lg px-8 py-4 rounded-full">Register</Button>
            </Link>
            <Link to="/menu">
              <Button variant="outline" className="border-[#C5A059] text-[#C5A059] font-bold text-lg px-8 py-4 rounded-full">View the menu</Button>
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}

export default AboutPage;
