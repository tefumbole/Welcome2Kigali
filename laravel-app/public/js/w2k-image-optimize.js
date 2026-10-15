(function (window) {
    if (window.w2kOptimizeImage) return;

    window.w2kOptimizeImage = function (file, maxBytes, maxEdge) {
        maxBytes = maxBytes || 100000;
        maxEdge = maxEdge || 800;
        return new Promise(function (resolve) {
            if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                resolve(file);
                return;
            }
            if (file.size && file.size <= maxBytes && file.type === 'image/jpeg') {
                var probe = new Image();
                var url = URL.createObjectURL(file);
                probe.onload = function () {
                    URL.revokeObjectURL(url);
                    if (Math.max(probe.width || 0, probe.height || 0) <= maxEdge) {
                        resolve(file);
                        return;
                    }
                    compress();
                };
                probe.onerror = function () {
                    URL.revokeObjectURL(url);
                    compress();
                };
                probe.src = url;
                return;
            }
            compress();

            function compress() {
                var url = URL.createObjectURL(file);
                var img = new Image();
                img.onload = function () {
                    URL.revokeObjectURL(url);
                    var edges = [maxEdge, 640, 480, 320, 240];
                    var qualities = [0.82, 0.74, 0.66, 0.58, 0.5, 0.42];
                    var ei = 0;
                    function tryEdge() {
                        var edge = edges[Math.min(ei, edges.length - 1)];
                        var w = img.width || 1;
                        var h = img.height || 1;
                        var scale = Math.min(1, edge / Math.max(w, h));
                        w = Math.max(1, Math.round(w * scale));
                        h = Math.max(1, Math.round(h * scale));
                        var canvas = document.createElement('canvas');
                        canvas.width = w;
                        canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, w, h);
                        ctx.drawImage(img, 0, 0, w, h);
                        var qi = 0;
                        function nextQ() {
                            var q = qualities[Math.min(qi, qualities.length - 1)];
                            canvas.toBlob(function (blob) {
                                if (!blob) {
                                    resolve(file);
                                    return;
                                }
                                if (blob.size <= maxBytes || (ei >= edges.length - 1 && qi >= qualities.length - 1)) {
                                    var base = String(file.name || 'image').replace(/\.[^.]+$/, '');
                                    resolve(new File([blob], base + '.jpg', { type: 'image/jpeg' }));
                                    return;
                                }
                                if (qi < qualities.length - 1) {
                                    qi += 1;
                                    nextQ();
                                    return;
                                }
                                ei += 1;
                                tryEdge();
                            }, 'image/jpeg', q);
                        }
                        nextQ();
                    }
                    tryEdge();
                };
                img.onerror = function () {
                    URL.revokeObjectURL(url);
                    resolve(file);
                };
                img.src = url;
            }
        });
    };
})(window);
