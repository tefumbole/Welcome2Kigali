import React, { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet';
import { COMPANY_NAME } from '@/constants/branding';

function CafeMenuPage() {
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    const load = async () => {
      try {
        const res = await fetch('/api/public/menu');
        if (!res.ok) throw new Error('Menu unavailable');
        const data = await res.json();
        if (mounted) setGroups(Array.isArray(data.groups) ? data.groups : []);
      } catch {
        if (mounted) setGroups([]);
      } finally {
        if (mounted) setLoading(false);
      }
    };
    load();
    return () => {
      mounted = false;
    };
  }, []);

  return (
    <>
      <Helmet>
        <title>Menu | {COMPANY_NAME}</title>
        <meta name="description" content="Welcome 2 Kigali cafe and restaurant menu. Coffee, tea, juices, smoothies, and food." />
      </Helmet>

      <section className="relative py-20 bg-black text-white">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(197,160,89,0.16),_transparent_65%)]" />
        <div className="relative max-w-4xl mx-auto px-4 text-center">
          <p className="text-[#C5A059] text-xs tracking-[0.4em] uppercase mb-4">Live. Connect. Thrive.</p>
          <h1 className="text-4xl md:text-6xl font-bold mb-4">
            Cafe & <span className="text-[#C5A059]">Restaurant</span>
          </h1>
          <p className="text-lg text-gray-300 max-w-2xl mx-auto">
            Crafted to perfection. Every beverage is served with our signature complimentary bite. Prices in FRW, tax included.
          </p>
        </div>
      </section>

      <section className="py-16 bg-[#F7F1E8]">
        <div className="max-w-4xl mx-auto px-4">
          {loading && <p className="text-center text-gray-500">Loading the menu…</p>}
          {!loading && groups.length === 0 && (
            <div className="bg-white border border-[#C5A059]/30 rounded-xl p-10 text-center text-gray-600">
              The menu will appear here after cafe products are seeded in the Welcome 2 Kigali database.
            </div>
          )}
          {groups.map((group) => (
            <div key={group.id || group.name} className="mb-14">
              <div className="flex items-center gap-4 mb-6">
                <h2 className="text-3xl font-bold text-black">{group.name}</h2>
                <div className="flex-1 h-px bg-[#C5A059]/40" />
              </div>
              {(!group.items || group.items.length === 0) ? (
                <p className="text-gray-500 italic">Food dishes will be listed here as they are added in admin → Product.</p>
              ) : (
                <div className="bg-white rounded-xl border border-[#C5A059]/20 overflow-hidden">
                  {group.items.map((item) => (
                    <div key={item.id || item.name} className="flex items-start justify-between gap-4 px-6 py-4 border-b border-gray-100 last:border-0">
                      <div>
                        <h3 className="font-semibold text-black">{item.name}</h3>
                        {item.details ? <p className="text-sm text-gray-500">{item.details}</p> : null}
                      </div>
                      <p className="text-[#C5A059] font-semibold whitespace-nowrap">
                        {Number(item.price).toLocaleString()} <span className="text-xs">FRW</span>
                      </p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))}
          <div className="mt-8 grid sm:grid-cols-2 gap-4">
            <div className="border border-[#C5A059]/40 rounded-xl p-6 bg-white">
              <p className="text-[#C5A059] text-xs tracking-widest uppercase mb-2">Dine in</p>
              <p className="text-gray-700">Elegantly served. Thoughtfully paired.</p>
            </div>
            <div className="border border-[#C5A059]/40 rounded-xl p-6 bg-white">
              <p className="text-[#C5A059] text-xs tracking-widest uppercase mb-2">Take away</p>
              <p className="text-gray-700">Beautifully packed. Always with your bite.</p>
            </div>
          </div>
          <p className="text-center text-gray-500 text-sm mt-8">Prices are in FRW | Tax included</p>
        </div>
      </section>
    </>
  );
}

export default CafeMenuPage;
