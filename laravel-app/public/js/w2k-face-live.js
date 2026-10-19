(function (window) {
    function delay(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function frame(video, w, h) {
        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(video, 0, 0, w, h);
        return canvas;
    }

    function skinCenter(canvas) {
        var ctx = canvas.getContext('2d');
        var data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        var sum = 0;
        var count = 0;
        var i;
        for (i = 0; i < data.length; i += 16) {
            var r = data[i];
            var g = data[i + 1];
            var b = data[i + 2];
            if (r > 95 && g > 40 && b > 20 && r > g && r > b && (r - g) > 15) {
                sum += ((i / 4) % canvas.width);
                count++;
            }
        }
        if (count < 40) return null;
        return sum / count / canvas.width;
    }

    function detectCenter(video) {
        var small = frame(video, 160, 120);
        if (window.FaceDetector) {
            try {
                var detector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
                return detector.detect(small).then(function (faces) {
                    if (faces && faces[0] && faces[0].boundingBox) {
                        var box = faces[0].boundingBox;
                        return (box.x + box.width / 2) / small.width;
                    }
                    return skinCenter(small);
                }).catch(function () {
                    return skinCenter(small);
                });
            } catch (e) {
                return Promise.resolve(skinCenter(small));
            }
        }
        return Promise.resolve(skinCenter(small));
    }

    function snapshot(video) {
        var w = video.videoWidth || 480;
        var h = video.videoHeight || 640;
        var maxW = 480;
        var scale = w > maxW ? maxW / w : 1;
        var canvas = frame(video, Math.round(w * scale), Math.round(h * scale));
        return canvas.toDataURL('image/jpeg', 0.72);
    }

    function start(video, onStatus, isCancelled) {
        var status = onStatus || function () {};
        var cancelled = isCancelled || function () { return false; };
        var started = Date.now();
        var phase = 'center';
        var hold = 0;
        var baseline = null;
        var firstSide = 0;
        var portrait = '';

        function fail(message) {
            var err = new Error(message || 'face');
            err.userMessage = message;
            return Promise.reject(err);
        }

        function tick() {
            if (cancelled()) return fail('stopped');
            if (Date.now() - started > 50000) return fail('timeout');
            if (!video || video.readyState < 2) return delay(200).then(tick);
            return detectCenter(video).then(function (cx) {
                if (cx === null || isNaN(cx)) {
                    hold = 0;
                    status(phase === 'center'
                        ? 'Look straight at the camera.'
                        : (phase === 'left' ? 'Turn your head to your left.' : 'Now turn your head to your right.'));
                    return delay(220).then(tick);
                }
                var side = 0;
                if (baseline !== null) {
                    if (cx > baseline + 0.10) side = 1;
                    else if (cx < baseline - 0.10) side = -1;
                }
                if (phase === 'center') {
                    if (Math.abs(cx - 0.5) < 0.18) {
                        hold++;
                        portrait = snapshot(video);
                        baseline = cx;
                        status('Hold still, looking straight ahead.');
                        if (hold >= 5) {
                            phase = 'left';
                            hold = 0;
                            status('Now turn your head to your left.');
                        }
                    } else {
                        hold = 0;
                        status('Look straight at the camera.');
                    }
                } else if (phase === 'left') {
                    if (side !== 0) {
                        hold++;
                        firstSide = side;
                        status('Good. Hold that turn.');
                        if (hold >= 4) {
                            phase = 'right';
                            hold = 0;
                            status('Now turn your head the other way.');
                        }
                    } else {
                        hold = 0;
                        status('Turn your head to your left.');
                    }
                } else if (side !== 0 && side !== firstSide) {
                    hold++;
                    status('Good. Hold that turn.');
                    if (hold >= 4 && portrait) {
                        return { image: portrait };
                    }
                } else {
                    hold = 0;
                    status('Now turn your head the other way.');
                }
                return delay(220).then(tick);
            });
        }

        status('Look straight at the camera.');
        return tick();
    }

    window.w2kFaceLive = { start: start };
})(window);
