(function () {
    'use strict';

    var config = window.secretsGalleryConfig || {};
    var PRELOAD_WINDOW = 10;
    var SWIPE_THRESHOLD = 60;
    var TAP_THRESHOLD = 10;
    var CONTROLS_HIDE_DELAY = 2500;

    var files = [];
    var hasMore = !!config.hasMore;
    var loading = false;

    var currentIndex = -1;
    var preloadPool = {};
    var plyrInstance = null;
    var controlsHideTimer = null;
    var zoomState = {scale: 1, x: 0, y: 0};

    var listEl = document.getElementById('secrets-gallery-list');
    var sentinelEl = document.getElementById('secrets-gallery-sentinel');
    var emptyEl = document.getElementById('secrets-gallery-empty');
    var modalEl = document.getElementById('secrets-modal');
    var stageEl = document.getElementById('secrets-modal-stage');
    var closeBtn = document.getElementById('secrets-modal-close');

    function viewUrl(id) {
        return config.viewUrlBase.replace('__ID__', id);
    }

    function isImage(mime) {
        return typeof mime === 'string' && mime.indexOf('image/') === 0;
    }

    function isVideo(mime) {
        return typeof mime === 'string' && mime.indexOf('video/') === 0;
    }

    // --- 一覧表示・無限スクロール ---

    function appendRecords(records) {
        records.forEach(function (r) {
            var index = files.length;
            files.push(r);

            var li = document.createElement('li');
            var a = document.createElement('a');
            a.href = 'javascript:void(0)';

            var nameSpan = document.createElement('span');
            nameSpan.textContent = r.name;
            var dateSpan = document.createElement('span');
            dateSpan.className = 'secrets-gallery-date';
            dateSpan.textContent = r.created_at || '';

            a.appendChild(nameSpan);
            a.appendChild(dateSpan);
            a.addEventListener('click', (function (i) {
                return function () { openModal(i); };
            })(index));

            li.appendChild(a);
            listEl.appendChild(li);
        });
    }

    function loadMore() {
        if (loading || !hasMore) {
            return;
        }
        loading = true;
        sentinelEl.style.display = '';

        var lastId = files.length ? files[files.length - 1].id : 0;
        fetch(config.listUrl + '?before_id=' + lastId, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (res) { return res.json(); })
            .then(function (data) {
                appendRecords(data.records || []);
                hasMore = !!data.has_more;
                sentinelEl.style.display = hasMore ? '' : 'none';
                loading = false;
                if (files.length === 0) {
                    emptyEl.style.display = '';
                }
            })
            .catch(function () {
                loading = false;
            });
    }

    appendRecords(config.initialRecords || []);
    if (files.length === 0 && !hasMore) {
        emptyEl.style.display = '';
    }
    sentinelEl.style.display = hasMore ? '' : 'none';

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) {
                loadMore();
            }
        });
        observer.observe(sentinelEl);
    }

    // --- 事前読み込み（前後10件）---

    function destroyPreload(id) {
        var entry = preloadPool[id];
        if (!entry) {
            return;
        }
        if (entry.type === 'video') {
            entry.el.pause();
            entry.el.removeAttribute('src');
            entry.el.load();
        } else {
            entry.el.src = '';
        }
        if (entry.el.parentNode) {
            entry.el.parentNode.removeChild(entry.el);
        }
        delete preloadPool[id];
    }

    function ensurePreload(index) {
        if (index < 0 || index >= files.length) {
            return;
        }
        var file = files[index];
        if (preloadPool[file.id]) {
            return;
        }
        if (isImage(file.mime_type)) {
            var img = new Image();
            img.src = viewUrl(file.id);
            preloadPool[file.id] = {el: img, type: 'image'};
        } else if (isVideo(file.mime_type)) {
            // 動画は全体を読み込むと通信量が過大になるため、メタデータ程度に留める
            var video = document.createElement('video');
            video.preload = 'metadata';
            video.muted = true;
            video.style.display = 'none';
            video.src = viewUrl(file.id);
            document.body.appendChild(video);
            preloadPool[file.id] = {el: video, type: 'video'};
        }
    }

    function updatePreloadWindow(index) {
        var keep = {};
        var from = Math.max(0, index - PRELOAD_WINDOW);
        var to = Math.min(files.length - 1, index + PRELOAD_WINDOW);
        for (var i = from; i <= to; i++) {
            keep[files[i].id] = true;
            ensurePreload(i);
        }
        Object.keys(preloadPool).forEach(function (id) {
            if (!keep[id]) {
                destroyPreload(id);
            }
        });
    }

    // --- モーダル本体 ---

    function clearStage() {
        if (plyrInstance) {
            try {
                plyrInstance.destroy();
            } catch (e) { /* noop */ }
            plyrInstance = null;
        }
        stageEl.innerHTML = '';
    }

    function applyZoom(imgEl) {
        imgEl.style.transform = 'translate(' + zoomState.x + 'px,' + zoomState.y + 'px) scale(' + zoomState.scale + ')';
    }

    function resetZoom(imgEl) {
        zoomState = {scale: 1, x: 0, y: 0};
        applyZoom(imgEl);
    }

    function renderImage(file) {
        var img = document.createElement('img');
        img.src = viewUrl(file.id);
        img.draggable = false;
        img.oncontextmenu = function () { return false; };
        stageEl.appendChild(img);
        resetZoom(img);
    }

    function renderVideo(file) {
        var video = document.createElement('video');
        video.setAttribute('playsinline', '');
        video.oncontextmenu = function () { return false; };
        video.src = viewUrl(file.id);
        stageEl.appendChild(video);
        plyrInstance = new Plyr(video, {
            controls: ['play-large', 'rewind', 'play', 'fast-forward', 'progress', 'current-time', 'mute', 'volume', 'fullscreen'],
            seekTime: 10,
            clickToPlay: false,
        });
        // Plyrのコンテナはただのdivで動画の縦横比を知らないため、幅いっぱいに広がってしまい
        // 縦長動画では高さがステージからはみ出す。実際のvideoWidth/videoHeightが分かった時点で
        // aspect-ratioを設定し、高さ基準で縦横比を保ったまま収まるようにする。
        video.addEventListener('loadedmetadata', function () {
            if (plyrInstance && plyrInstance.elements && plyrInstance.elements.container && video.videoWidth && video.videoHeight) {
                plyrInstance.elements.container.style.aspectRatio = video.videoWidth + ' / ' + video.videoHeight;
            }
        });
    }

    function renderCurrent() {
        clearStage();
        var file = files[currentIndex];
        if (!file) {
            return;
        }
        if (isImage(file.mime_type)) {
            renderImage(file);
        } else if (isVideo(file.mime_type)) {
            renderVideo(file);
        }
        updatePreloadWindow(currentIndex);
    }

    function openModal(index) {
        currentIndex = index;
        modalEl.style.display = 'block';
        document.body.style.overflow = 'hidden';
        renderCurrent();
    }

    function closeModal() {
        modalEl.style.display = 'none';
        document.body.style.overflow = '';
        clearStage();
        Object.keys(preloadPool).forEach(destroyPreload);
    }

    function navigate(delta) {
        var next = currentIndex + delta;
        if (next < 0 || next >= files.length) {
            return;
        }
        currentIndex = next;
        renderCurrent();
        if (currentIndex >= files.length - 5) {
            loadMore();
        }
    }

    closeBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
        if (modalEl.style.display === 'none') {
            return;
        }
        if (e.key === 'Escape') {
            closeModal();
        } else if (e.key === 'ArrowLeft') {
            navigate(-1);
        } else if (e.key === 'ArrowRight') {
            navigate(1);
        }
    });

    // --- 動画: シークヒント表示・コントロール一時表示 ---

    function showSeekHint(side) {
        var hint = document.createElement('div');
        hint.className = 'secrets-modal-seek-hint ' + side + ' is-visible';
        hint.textContent = side === 'left' ? '−10s' : '+10s';
        modalEl.appendChild(hint);
        setTimeout(function () {
            hint.classList.remove('is-visible');
            setTimeout(function () {
                if (hint.parentNode) {
                    hint.parentNode.removeChild(hint);
                }
            }, 200);
        }, 500);
    }

    function revealControlsTemporarily() {
        if (!plyrInstance || !plyrInstance.elements || !plyrInstance.elements.container) {
            return;
        }
        var container = plyrInstance.elements.container;
        container.classList.remove('plyr--hide-controls');
        clearTimeout(controlsHideTimer);
        controlsHideTimer = setTimeout(function () {
            container.classList.add('plyr--hide-controls');
        }, CONTROLS_HIDE_DELAY);
    }

    // --- ステージ全体でのジェスチャー処理（画像のズーム/パン/スワイプ、動画のシーク/スワイプ）---

    (function attachStageGestures() {
        var pointers = {};
        var pinchStartDist = null;
        var pinchStartScale = 1;
        var dragStart = null;
        var moved = false;
        var lastTapAt = 0;

        function dist(p1, p2) {
            return Math.hypot(p1.x - p2.x, p1.y - p2.y);
        }

        function isOnPlyrControls(target) {
            return !!(target.closest && target.closest('.plyr__controls, .plyr__control'));
        }

        function currentFile() {
            return files[currentIndex];
        }

        stageEl.addEventListener('pointerdown', function (e) {
            if (isOnPlyrControls(e.target)) {
                return;
            }
            pointers[e.pointerId] = {x: e.clientX, y: e.clientY};
            moved = false;

            var ids = Object.keys(pointers);
            if (ids.length === 2) {
                pinchStartDist = dist(pointers[ids[0]], pointers[ids[1]]);
                pinchStartScale = zoomState.scale;
            } else if (ids.length === 1) {
                dragStart = {x: e.clientX, y: e.clientY, zx: zoomState.x, zy: zoomState.y};
            }
        });

        stageEl.addEventListener('pointermove', function (e) {
            if (!pointers[e.pointerId]) {
                return;
            }
            pointers[e.pointerId] = {x: e.clientX, y: e.clientY};
            var ids = Object.keys(pointers);
            var file = currentFile();
            var imgEl = isImage(file && file.mime_type) ? stageEl.querySelector('img') : null;

            if (ids.length === 2 && pinchStartDist && imgEl) {
                var d = dist(pointers[ids[0]], pointers[ids[1]]);
                zoomState.scale = Math.min(5, Math.max(1, pinchStartScale * (d / pinchStartDist)));
                applyZoom(imgEl);
                moved = true;
                return;
            }

            if (ids.length === 1 && dragStart) {
                var dx = e.clientX - dragStart.x;
                var dy = e.clientY - dragStart.y;
                if (Math.abs(dx) > TAP_THRESHOLD || Math.abs(dy) > TAP_THRESHOLD) {
                    moved = true;
                }
                if (imgEl && zoomState.scale > 1) {
                    zoomState.x = dragStart.zx + dx;
                    zoomState.y = dragStart.zy + dy;
                    applyZoom(imgEl);
                }
            }
        });

        function endPointer(e) {
            if (!pointers[e.pointerId]) {
                return;
            }
            var wasSingle = Object.keys(pointers).length === 1;
            var start = dragStart;
            delete pointers[e.pointerId];
            if (Object.keys(pointers).length < 2) {
                pinchStartDist = null;
            }
            if (!wasSingle || !start) {
                dragStart = null;
                return;
            }

            var file = currentFile();
            var isVid = isVideo(file && file.mime_type);
            var dx = e.clientX - start.x;
            var rect = stageEl.getBoundingClientRect();
            var isLeftHalf = (e.clientX - rect.left) < rect.width / 2;

            if (isVid) {
                if (moved && Math.abs(dx) > SWIPE_THRESHOLD) {
                    // 横スワイプ: 動画同士でも次/前のファイルへ移動する
                    navigate(dx < 0 ? 1 : -1);
                } else if (!moved) {
                    handleVideoZoneClick(isLeftHalf ? 'left' : 'right');
                }
            } else {
                // 画像: ズーム中は移動のみ（パン扱い）、そうでなければタップ/スワイプでナビゲート
                if (zoomState.scale <= 1) {
                    if (moved && Math.abs(dx) > SWIPE_THRESHOLD) {
                        navigate(dx < 0 ? 1 : -1);
                    } else if (!moved) {
                        var now = Date.now();
                        if (now - lastTapAt < 300) {
                            // ダブルタップ: ズームのトグル（dblclickも別途処理するが、タッチ端末用に補完）
                            var imgElTap = stageEl.querySelector('img');
                            if (imgElTap) {
                                zoomState.scale = zoomState.scale > 1 ? 1 : 2.5;
                                zoomState.x = 0;
                                zoomState.y = 0;
                                applyZoom(imgElTap);
                            }
                            lastTapAt = 0;
                        } else {
                            lastTapAt = now;
                            navigate(isLeftHalf ? -1 : 1);
                        }
                    }
                }
            }

            dragStart = null;
        }

        stageEl.addEventListener('pointerup', endPointer);
        stageEl.addEventListener('pointercancel', endPointer);

        stageEl.addEventListener('dblclick', function (e) {
            var imgEl = stageEl.querySelector('img');
            if (!imgEl) {
                return;
            }
            zoomState.scale = zoomState.scale > 1 ? 1 : 2.5;
            zoomState.x = 0;
            zoomState.y = 0;
            applyZoom(imgEl);
        });
    })();

    function handleVideoZoneClick(side) {
        if (!plyrInstance) {
            return;
        }
        var delta = side === 'left' ? -10 : 10;
        var duration = plyrInstance.duration || 0;
        plyrInstance.currentTime = Math.max(0, Math.min(duration, plyrInstance.currentTime + delta));
        showSeekHint(side);
        revealControlsTemporarily();
    }
})();
