(function (window) {
    var ISO_NAMES = {
        AFG: 'Afghanistan', ALB: 'Albania', DZA: 'Algeria', AND: 'Andorra', AGO: 'Angola',
        ARG: 'Argentina', ARM: 'Armenia', AUS: 'Australia', AUT: 'Austria', AZE: 'Azerbaijan',
        BHR: 'Bahrain', BGD: 'Bangladesh', BEL: 'Belgium', BEN: 'Benin', BTN: 'Bhutan',
        BOL: 'Bolivia', BIH: 'Bosnia and Herzegovina', BWA: 'Botswana', BRA: 'Brazil',
        BRN: 'Brunei', BGR: 'Bulgaria', BFA: 'Burkina Faso', BDI: 'Burundi',
        KHM: 'Cambodia', CMR: 'Cameroon', CAN: 'Canada', CPV: 'Cabo Verde', CAF: 'Central African Republic',
        TCD: 'Chad', CHL: 'Chile', CHN: 'China', COL: 'Colombia', COM: 'Comoros',
        COG: 'Congo', COD: 'DR Congo', CRI: 'Costa Rica', CIV: 'Côte d’Ivoire', HRV: 'Croatia',
        CUB: 'Cuba', CYP: 'Cyprus', CZE: 'Czechia', DNK: 'Denmark', DJI: 'Djibouti',
        DOM: 'Dominican Republic', ECU: 'Ecuador', EGY: 'Egypt', SLV: 'El Salvador',
        GNQ: 'Equatorial Guinea', ERI: 'Eritrea', EST: 'Estonia', SWZ: 'Eswatini', ETH: 'Ethiopia',
        FIN: 'Finland', FRA: 'France', GAB: 'Gabon', GMB: 'Gambia', GEO: 'Georgia',
        DEU: 'Germany', GHA: 'Ghana', GRC: 'Greece', GTM: 'Guatemala', GIN: 'Guinea',
        GNB: 'Guinea-Bissau', GUY: 'Guyana', HTI: 'Haiti', HND: 'Honduras', HUN: 'Hungary',
        ISL: 'Iceland', IND: 'India', IDN: 'Indonesia', IRN: 'Iran', IRQ: 'Iraq',
        IRL: 'Ireland', ISR: 'Israel', ITA: 'Italy', JAM: 'Jamaica', JPN: 'Japan',
        JOR: 'Jordan', KAZ: 'Kazakhstan', KEN: 'Kenya', KOR: 'South Korea', KWT: 'Kuwait',
        KGZ: 'Kyrgyzstan', LAO: 'Laos', LVA: 'Latvia', LBN: 'Lebanon', LSO: 'Lesotho',
        LBR: 'Liberia', LBY: 'Libya', LIE: 'Liechtenstein', LTU: 'Lithuania', LUX: 'Luxembourg',
        MDG: 'Madagascar', MWI: 'Malawi', MYS: 'Malaysia', MDV: 'Maldives', MLI: 'Mali',
        MLT: 'Malta', MRT: 'Mauritania', MUS: 'Mauritius', MEX: 'Mexico', MDA: 'Moldova',
        MCO: 'Monaco', MNG: 'Mongolia', MNE: 'Montenegro', MAR: 'Morocco', MOZ: 'Mozambique',
        MMR: 'Myanmar', NAM: 'Namibia', NPL: 'Nepal', NLD: 'Netherlands', NZL: 'New Zealand',
        NIC: 'Nicaragua', NER: 'Niger', NGA: 'Nigeria', MKD: 'North Macedonia', NOR: 'Norway',
        OMN: 'Oman', PAK: 'Pakistan', PAN: 'Panama', PNG: 'Papua New Guinea', PRY: 'Paraguay',
        PER: 'Peru', PHL: 'Philippines', POL: 'Poland', PRT: 'Portugal', QAT: 'Qatar',
        ROU: 'Romania', RUS: 'Russia', RWA: 'Rwanda', KNA: 'Saint Kitts and Nevis',
        LCA: 'Saint Lucia', SAU: 'Saudi Arabia', SEN: 'Senegal', SRB: 'Serbia', SYC: 'Seychelles',
        SLE: 'Sierra Leone', SGP: 'Singapore', SVK: 'Slovakia', SVN: 'Slovenia', SOM: 'Somalia',
        ZAF: 'South Africa', SSD: 'South Sudan', ESP: 'Spain', LKA: 'Sri Lanka', SDN: 'Sudan',
        SUR: 'Suriname', SWE: 'Sweden', CHE: 'Switzerland', SYR: 'Syria', TWN: 'Taiwan',
        TZA: 'Tanzania', THA: 'Thailand', TGO: 'Togo', TTO: 'Trinidad and Tobago',
        TUN: 'Tunisia', TUR: 'Turkey', UGA: 'Uganda', UKR: 'Ukraine', ARE: 'United Arab Emirates',
        GBR: 'United Kingdom', USA: 'United States', URY: 'Uruguay', UZB: 'Uzbekistan',
        VEN: 'Venezuela', VNM: 'Vietnam', YEM: 'Yemen', ZMB: 'Zambia', ZWE: 'Zimbabwe',
        UTO: 'Utopia'
    };

    function titleCase(s) {
        return String(s || '').toLowerCase().replace(/(^|[\s\-'])([a-z])/g, function (_, a, b) {
            return a + b.toUpperCase();
        });
    }

    function cleanName(s) {
        s = String(s || '').replace(/[<]+/g, ' ').replace(/\s+/g, ' ').trim();
        s = s.replace(/[^A-Za-zÀ-ÿ'\-\s]/g, ' ').replace(/\s+/g, ' ').trim();
        if (s.length < 3) return '';
        return titleCase(s);
    }

    function nationalityName(code) {
        code = String(code || '').replace(/</g, '').toUpperCase().trim();
        if (!/^[A-Z]{3}$/.test(code) || code === 'XXX') return '';
        return ISO_NAMES[code] || code;
    }

    function mrzName(line) {
        var parts = line.split('<<');
        var last = (parts[0] || '').replace(/</g, ' ').replace(/\s+/g, ' ').trim();
        var first = (parts[1] || '').replace(/</g, ' ').replace(/\s+/g, ' ').trim();
        return cleanName((first + ' ' + last).trim());
    }

    function mrzDate(yyMMdd, kind) {
        var d = String(yyMMdd || '').replace(/\D/g, '');
        if (d.length !== 6) return '';
        var yy = parseInt(d.slice(0, 2), 10);
        var mm = d.slice(2, 4);
        var dd = d.slice(4, 6);
        if (mm < '01' || mm > '12' || dd < '01' || dd > '31') return '';
        var nowY = new Date().getFullYear();
        var year = 2000 + yy;
        if (kind === 'dob' && year > nowY) year = 1900 + yy;
        if (kind === 'exp' && year < nowY - 15) year = 2000 + yy;
        return year + '-' + mm + '-' + dd;
    }

    function emptyResult() {
        return { full_name: '', id_number: '', id_type: '', date_of_birth: '', expires_on: '', nationality: '' };
    }

    function parseMrz(text) {
        var compact = String(text || '').toUpperCase().replace(/[^A-Z0-9<\n]/g, '');
        var lines = compact.split(/\n+/).map(function (l) { return l.replace(/\s+/g, ''); }).filter(Boolean);
        var blob = compact.replace(/[\s\n]+/g, '');
        var result = emptyResult();

        var td3 = blob.match(/P[A-Z<][A-Z]{3}[A-Z<]{39}[A-Z0-9<]{44}/);
        if (td3) {
            var a = td3[0].slice(0, 44);
            var b = td3[0].slice(44, 88);
            result.id_type = 'passport';
            result.full_name = mrzName(a.slice(5));
            result.id_number = b.slice(0, 9).replace(/</g, '');
            result.date_of_birth = mrzDate(b.slice(13, 19), 'dob');
            result.expires_on = mrzDate(b.slice(21, 27), 'exp');
            result.nationality = nationalityName(b.slice(10, 13)) || nationalityName(a.slice(2, 5));
            return result;
        }

        for (var i = 0; i < lines.length; i++) {
            if (lines[i].charAt(0) === 'P' && lines[i].length >= 40) {
                result.id_type = 'passport';
                result.full_name = mrzName(lines[i].slice(5));
                result.nationality = nationalityName(lines[i].slice(2, 5));
                if (lines[i + 1] && lines[i + 1].length >= 27) {
                    result.id_number = lines[i + 1].slice(0, 9).replace(/</g, '');
                    result.date_of_birth = mrzDate(lines[i + 1].slice(13, 19), 'dob');
                    result.expires_on = mrzDate(lines[i + 1].slice(21, 27), 'exp');
                    result.nationality = nationalityName(lines[i + 1].slice(10, 13)) || result.nationality;
                }
                if (result.full_name) return result;
            }
            if ((lines[i].charAt(0) === 'I' || lines[i].indexOf('ID') === 0) && lines[i].length >= 28) {
                result.id_type = 'national_id';
                result.id_number = lines[i].slice(5, 14).replace(/</g, '');
                result.nationality = nationalityName(lines[i].slice(2, 5));
                if (lines[i + 1] && lines[i + 1].length >= 18) {
                    result.date_of_birth = mrzDate(lines[i + 1].slice(0, 6), 'dob');
                    result.expires_on = mrzDate(lines[i + 1].slice(8, 14), 'exp');
                    result.nationality = nationalityName(lines[i + 1].slice(15, 18)) || result.nationality;
                }
                if (lines[i + 2]) result.full_name = mrzName(lines[i + 2]);
                if (result.full_name) return result;
            }
        }
        return result;
    }

    function parseIsoDate(raw) {
        var s = String(raw || '').trim();
        var m = s.match(/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/);
        if (m) {
            var day = ('0' + m[1]).slice(-2);
            var month = ('0' + m[2]).slice(-2);
            var y = m[3];
            if (parseInt(m[1], 10) > 12 && parseInt(m[2], 10) <= 12) {
                return y + '-' + month + '-' + day;
            }
            if (parseInt(m[2], 10) > 12 && parseInt(m[1], 10) <= 12) {
                return y + '-' + day + '-' + month;
            }
            return y + '-' + month + '-' + day;
        }
        m = s.match(/(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/);
        if (m) return m[1] + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[3]).slice(-2);
        return '';
    }

    function parseLabels(text) {
        var raw = String(text || '').replace(/\r/g, '\n');
        var result = emptyResult();
        var m;
        m = raw.match(/(?:surname|nom(?:\s*\/\s*surname)?|last\s*name)\s*[:\-]?\s*([A-Za-zÀ-ÿ'\-\s]{2,40})/i);
        var last = m ? cleanName(m[1]) : '';
        m = raw.match(/(?:given names?|pr[eé]noms?(?:\s*\/\s*given)?|first\s*name)\s*[:\-]?\s*([A-Za-zÀ-ÿ'\-\s]{2,50})/i);
        var first = m ? cleanName(m[1]) : '';
        if (first || last) result.full_name = cleanName((first + ' ' + last).trim());
        if (!result.full_name) {
            m = raw.match(/(?:full name|names?|titulaire)\s*[:\-]?\s*([A-Za-zÀ-ÿ'\-\s]{5,60})/i);
            if (m) result.full_name = cleanName(m[1]);
        }
        m = raw.match(/(?:passport\s*no|document\s*no|n[°o]\s*(?:du\s*)?(?:document|passeport)|id\s*no|nin|national\s*id)\s*[:\.]?\s*([A-Z0-9]{5,20})/i);
        if (m) result.id_number = m[1].toUpperCase();
        if (!result.id_number) {
            m = raw.replace(/\s+/g, ' ').match(/\b(1\s?\d{4}\s?\d\s?\d{7}\s?\d\s?\d)\b/);
            if (m) result.id_number = m[1].replace(/\s+/g, '');
        }
        if (!result.id_number) {
            m = raw.replace(/[^\d]/g, ' ').match(/\b(\d{16})\b/);
            if (m) result.id_number = m[1];
        }
        m = raw.match(/(?:date of birth|birth(?:day)?|n[eé](?:e)?\s*le|date de naissance|dob)\s*[:\-]?\s*([0-9]{1,4}[\/\-.][0-9]{1,2}[\/\-.][0-9]{2,4})/i);
        if (m) result.date_of_birth = parseIsoDate(m[1]);
        m = raw.match(/(?:date of expiry|expiry|expiration|valid until|date d['’]expir|valable jusqu)\s*[:\-]?\s*([0-9]{1,4}[\/\-.][0-9]{1,2}[\/\-.][0-9]{2,4})/i);
        if (m) result.expires_on = parseIsoDate(m[1]);
        m = raw.match(/(?:nationality|nationalit[eé]|citoyennet[eé]|citizenship)\s*[:\-]?\s*([A-Za-zÀ-ÿ'\-\s]{3,40})/i);
        if (m) {
            var nat = nationalityName(m[1]) || titleCase(m[1].replace(/[^A-Za-zÀ-ÿ'\-\s]/g, ' ').trim());
            if (nat.length >= 3) result.nationality = nat;
        }
        return result;
    }

    function merge(a, b) {
        return {
            full_name: a.full_name || b.full_name || '',
            id_number: a.id_number || b.id_number || '',
            id_type: a.id_type || b.id_type || '',
            date_of_birth: a.date_of_birth || b.date_of_birth || '',
            expires_on: a.expires_on || b.expires_on || '',
            nationality: a.nationality || b.nationality || ''
        };
    }

    function isUseful(result) {
        return !!(result && result.full_name && result.id_number);
    }

    function loadImage(src) {
        return new Promise(function (resolve, reject) {
            var img = new Image();
            img.onload = function () { resolve(img); };
            img.onerror = reject;
            img.src = src;
        });
    }

    function toCanvas(fileOrUrl, maxW) {
        return new Promise(function (resolve, reject) {
            var done = function (src) {
                loadImage(src).then(function (img) {
                    var w = img.naturalWidth || img.width;
                    var h = img.naturalHeight || img.height;
                    var scale = w > maxW ? maxW / w : 1;
                    var canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(w * scale));
                    canvas.height = Math.max(1, Math.round(h * scale));
                    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                    resolve(canvas);
                }).catch(reject);
            };
            if (typeof fileOrUrl === 'string') {
                done(fileOrUrl);
            } else {
                var reader = new FileReader();
                reader.onload = function (e) { done(e.target.result); };
                reader.onerror = reject;
                reader.readAsDataURL(fileOrUrl);
            }
        });
    }

    function cropBottom(canvas, ratio) {
        var h = Math.max(40, Math.round(canvas.height * ratio));
        var out = document.createElement('canvas');
        out.width = canvas.width;
        out.height = h;
        out.getContext('2d').drawImage(canvas, 0, canvas.height - h, canvas.width, h, 0, 0, canvas.width, h);
        return out;
    }

    function frameFromVideo(video) {
        var w = video.videoWidth || 640;
        var h = video.videoHeight || 480;
        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(video, 0, 0, w, h);
        return canvas;
    }

    function ensureTesseract() {
        if (window.Tesseract) return Promise.resolve(window.Tesseract);
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js';
            s.onload = function () { resolve(window.Tesseract); };
            s.onerror = function () { reject(new Error('Could not load document reader')); };
            document.head.appendChild(s);
        });
    }

    var mrzWorker = null;
    function getMrzWorker() {
        if (mrzWorker) return Promise.resolve(mrzWorker);
        return ensureTesseract().then(function (Tesseract) {
            return Tesseract.createWorker({ logger: function () {} }).then(function (worker) {
                return worker.loadLanguage('eng').then(function () {
                    return worker.initialize('eng');
                }).then(function () {
                    return worker.setParameters({
                        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
                        tessedit_pageseg_mode: '6'
                    });
                }).then(function () {
                    mrzWorker = worker;
                    return worker;
                });
            });
        });
    }

    function recognize(source, mrzOnly) {
        if (mrzOnly) {
            return getMrzWorker().then(function (worker) {
                return worker.recognize(source).then(function (res) {
                    return (res && res.data && res.data.text) ? res.data.text : '';
                });
            });
        }
        return ensureTesseract().then(function (Tesseract) {
            return Tesseract.recognize(source, 'eng').then(function (res) {
                return (res && res.data && res.data.text) ? res.data.text : '';
            });
        });
    }

    function readDocument(fileOrUrl, onStatus) {
        var status = onStatus || function () {};
        status('Reading the document…');
        return toCanvas(fileOrUrl, 1400).then(function (canvas) {
            return recognize(canvas, false).then(function (fullText) {
                var fromFull = merge(parseMrz(fullText), parseLabels(fullText));
                if (isUseful(fromFull) && fromFull.expires_on && fromFull.nationality) {
                    return fromFull;
                }
                status('Reading the machine-readable lines…');
                return recognize(cropBottom(canvas, 0.42), true).then(function (mrzText) {
                    return merge(parseMrz(mrzText), fromFull);
                });
            });
        });
    }

    function delay(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function scanLive(video, onStatus, isCancelled) {
        var status = onStatus || function () {};
        var cancelled = isCancelled || function () { return false; };
        status('Hold the bottom lines of the ID in the frame…');
        return getMrzWorker().then(function () {
            function tick() {
                if (cancelled()) return Promise.reject(new Error('stopped'));
                if (!video || video.readyState < 2) {
                    return delay(300).then(tick);
                }
                var frame = frameFromVideo(video);
                return recognize(cropBottom(frame, 0.45), true).then(function (mrzText) {
                    var result = parseMrz(mrzText);
                    if (isUseful(result)) return result;
                    return recognize(frame, false).then(function (fullText) {
                        result = merge(parseMrz(fullText), parseLabels(fullText));
                        if (isUseful(result)) return result;
                        status('Keep the ID steady. We are reading the details — no photo is saved.');
                        return delay(900).then(tick);
                    });
                });
            }
            return tick();
        });
    }

    window.w2kIdRead = {
        readDocument: readDocument,
        scanLive: scanLive,
        parseMrz: parseMrz
    };
})(window);
