/* GrooveVault shared player — one set of controls, four playback engines:
     • file        — <video> element: real video MP4s show the picture, audio-only
                      files (MP3, Suno, direct links) fall back to the album art.
     • spotify      — Spotify IFrame embed (audio)
     • youtube      — YouTube IFrame player (video)
     • soundcloud   — SoundCloud Widget (audio)
   Used by player.php and public_channel.php. Build a config and call .start().   */
(function (global) {
  'use strict';
  function noop() {}
  function fmt(s) { s = Math.floor(s || 0); if (s < 0) s = 0; return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); }

  /* ---------------- URL detection (mirrors inc/functions.inc.php) ---------------- */
  function spotifyUri(u) {
    u = (u || '').trim();
    var m = u.match(/^spotify:(track|album|playlist|episode|show):([A-Za-z0-9]+)/);
    if (m) return 'spotify:' + m[1] + ':' + m[2];
    m = u.match(/^https?:\/\/open\.spotify\.com\/(?:intl-[a-z]{2}\/)?(track|album|playlist|episode|show)\/([A-Za-z0-9]+)/i);
    if (m) return 'spotify:' + m[1].toLowerCase() + ':' + m[2];
    return null;
  }
  function youtubeId(u) {
    u = (u || '').trim();
    var m = u.match(/^https?:\/\/youtu\.be\/([A-Za-z0-9_-]{11})/i); if (m) return m[1];
    m = u.match(/^https?:\/\/(?:www\.|m\.|music\.)?youtube\.com\/watch\?(?:[^#]*&)?v=([A-Za-z0-9_-]{11})/i); if (m) return m[1];
    m = u.match(/^https?:\/\/(?:www\.|m\.)?youtube\.com\/(?:shorts|embed|v|live)\/([A-Za-z0-9_-]{11})/i); if (m) return m[1];
    return null;
  }
  function soundcloudUrl(u) {
    u = (u || '').trim();
    if (/^https?:\/\/(?:www\.|m\.)?soundcloud\.com\/[A-Za-z0-9_-]+\/.+/i.test(u)) return u;
    if (/^https?:\/\/on\.soundcloud\.com\/[A-Za-z0-9]+/i.test(u)) return u;
    return null;
  }
  function classify(u) {
    var r;
    if ((r = spotifyUri(u)))    return { kind: 'spotify', ref: r };
    if ((r = youtubeId(u)))     return { kind: 'youtube', ref: r };
    if ((r = soundcloudUrl(u))) return { kind: 'soundcloud', ref: r };
    return null;
  }

  /* ---------------- Lazy third-party API loaders ---------------- */
  function inject(src) { var s = document.createElement('script'); s.src = src; document.head.appendChild(s); }

  var _spot = { api: null, q: [], loading: false };
  function withSpotify(cb) {
    if (_spot.api) return cb(_spot.api);
    _spot.q.push(cb);
    if (_spot.loading) return; _spot.loading = true;
    global.onSpotifyIframeApiReady = function (api) { _spot.api = api; _spot.q.forEach(function (f) { f(api); }); _spot.q = []; };
    inject('https://open.spotify.com/embed/iframe-api/v1');
  }
  var _yt = { q: [], loading: false };
  function withYouTube(cb) {
    if (global.YT && global.YT.Player) return cb(global.YT);
    _yt.q.push(cb);
    if (_yt.loading) return; _yt.loading = true;
    var prev = global.onYouTubeIframeAPIReady;
    global.onYouTubeIframeAPIReady = function () { if (prev) try { prev(); } catch (e) {} _yt.q.forEach(function (f) { f(global.YT); }); _yt.q = []; };
    inject('https://www.youtube.com/iframe_api');
  }
  var _sc = { q: [], loading: false };
  function withSoundCloud(cb) {
    if (global.SC && global.SC.Widget) return cb(global.SC);
    _sc.q.push(cb);
    if (_sc.loading) return; _sc.loading = true;
    var s = document.createElement('script');
    s.src = 'https://w.soundcloud.com/player/api.js';
    s.onload = function () { _sc.q.forEach(function (f) { f(global.SC); }); _sc.q = []; };
    document.head.appendChild(s);
  }

  /* ---------------- Duration auto-detection (used by add_track.php) ----------------
     Reads a track's length without committing it to the page player. cb(seconds|0). */
  function offscreen() { var d = document.createElement('div'); d.style.cssText = 'position:absolute;left:-9999px;top:0;width:320px;height:180px;'; document.body.appendChild(d); return d; }
  function rm(el) { if (el && el.parentNode) el.parentNode.removeChild(el); }

  function probeMedia(src, revoke, finish) {
    var p = document.createElement('video'); p.preload = 'metadata'; var done = false;
    function clean() { if (revoke) { try { URL.revokeObjectURL(src); } catch (e) {} } p.removeAttribute('src'); }
    p.addEventListener('loadedmetadata', function () { done = true; var ok = isFinite(p.duration) && p.duration > 0; clean(); finish(ok ? p.duration : 0); });
    p.addEventListener('error', function () { done = true; clean(); finish(0); });
    setTimeout(function () { if (!done) { clean(); finish(0); } }, 8000);
    p.src = src;
  }
  function detectSpotify(uri, finish) {
    var host = offscreen(), done = false;
    function fin(s) { if (done) return; done = true; rm(host); finish(s || 0); }
    withSpotify(function (api) {
      api.createController(host, { uri: uri, width: 300, height: 80 }, function (ctl) {
        ctl.addListener('playback_update', function (e) { if (e && e.data && e.data.duration > 0) fin(e.data.duration / 1000); });
      });
    });
    setTimeout(function () { fin(0); }, 9000);
  }
  function detectYouTube(id, finish) {
    var host = offscreen(), inner = document.createElement('div'); host.appendChild(inner);
    var done = false, player = null, poll = null;
    function fin(s) { if (done) return; done = true; if (poll) clearInterval(poll); try { if (player) player.destroy(); } catch (e) {} rm(host); finish(s || 0); }
    withYouTube(function (YT) {
      player = new YT.Player(inner, {
        width: 200, height: 120, videoId: id, playerVars: { autoplay: 0, playsinline: 1 },
        events: { onReady: function () { try { player.mute(); player.playVideo(); } catch (e) {} poll = setInterval(function () { try { var d = player.getDuration(); if (d > 0) { player.pauseVideo(); fin(d); } } catch (e) {} }, 300); } }
      });
    });
    setTimeout(function () { fin(0); }, 9000);
  }
  function detectSoundCloud(url, finish) {
    var host = offscreen(), iframe = document.createElement('iframe'); iframe.style.cssText = 'width:300px;height:120px;border:0;';
    iframe.src = 'https://w.soundcloud.com/player/?url=' + encodeURIComponent(url) + '&auto_play=false';
    host.appendChild(iframe); var done = false;
    function fin(s) { if (done) return; done = true; rm(host); finish(s || 0); }
    withSoundCloud(function (SC) { var w = SC.Widget(iframe); w.bind(SC.Widget.Events.READY, function () { w.getDuration(function (ms) { fin((ms || 0) / 1000); }); }); });
    setTimeout(function () { fin(0); }, 9000);
  }
  function detectDuration(input, cb) {
    var done = false; function finish(s) { if (done) return; done = true; cb(s > 0 ? Math.round(s) : 0); }
    if (input && typeof input !== 'string') { probeMedia(URL.createObjectURL(input), true, finish); return; }
    var url = ('' + input).trim(); if (!url) { finish(0); return; }
    var c = classify(url);
    if (!c) return probeMedia(url, false, finish);
    if (c.kind === 'spotify')    return detectSpotify(c.ref, finish);
    if (c.kind === 'youtube')    return detectYouTube(c.ref, finish);
    if (c.kind === 'soundcloud') return detectSoundCloud(c.ref, finish);
    probeMedia(url, false, finish);
  }

  /* ---------------- The player ---------------- */
  function GVPlayer(config) {
    var playlist = config.playlist || [];
    var els = config.els || {};
    var audio = els.audio, artFallback = els.artFallback, embedHost = els.embedHost;
    var onState = config.onState || noop;

    var idx = 0, playing = false;
    var shuffle = !!config.shuffle, repeat = !!config.repeat;
    var volume = (config.volume == null ? 0.8 : config.volume);
    var engineKind = 'file';
    var P = {};          // kind -> engine instance (lazy)
    var children = {};   // kind -> embed wrapper element (for show/hide)

    function state() { onState({ playing: playing, idx: idx, shuffle: shuffle, repeat: repeat, kind: engineKind }); }
    function setNP(title) { if (els.npTitle) els.npTitle.textContent = title; }
    function setDur(d) { if (els.durTime) els.durTime.textContent = fmt(d); }
    function setProgress(cur, dur) {
      if (els.curTime) els.curTime.textContent = fmt(cur);
      if (dur && els.durTime) els.durTime.textContent = fmt(dur);
      if (els.progressFill) els.progressFill.style.width = (dur ? (cur / dur * 100) : 0) + '%';
    }
    function emitProgress(cur, dur) { if (els.curTime || els.progressFill) setProgress(cur, dur); }
    function emitPlaying(p) { playing = p; state(); }
    function emitEnded() { if (repeat) loadIndex(idx, true); else next(); }

    function setActiveHost(kind, hasVideo) {
      if (kind === 'file') {
        if (embedHost) embedHost.style.display = 'none';
        for (var k in children) if (children[k]) children[k].style.display = 'none';
        if (audio) audio.style.display = hasVideo ? 'block' : 'none';
        if (artFallback) artFallback.style.display = hasVideo ? 'none' : 'flex';
      } else {
        if (audio) audio.style.display = 'none';
        if (artFallback) artFallback.style.display = 'none';
        if (embedHost) embedHost.style.display = 'block';
        for (var j in children) if (children[j]) children[j].style.display = (j === kind) ? 'flex' : 'none';
      }
    }
    function mkWrap() { var w = document.createElement('div'); w.style.cssText = 'position:absolute;inset:0;display:none;align-items:center;justify-content:center;width:100%;height:100%;'; if (embedHost) embedHost.appendChild(w); return w; }

    /* -- file engine -- */
    function fileEngine() {
      function syncVideo() { if (engineKind === 'file') setActiveHost('file', audio.videoWidth > 0); }
      audio.addEventListener('timeupdate', function () { if (engineKind !== 'file') return; var d = audio.duration || (playlist[idx] && playlist[idx].duration) || 0; emitProgress(audio.currentTime, d); });
      audio.addEventListener('play', function () { if (engineKind === 'file') emitPlaying(true); });
      audio.addEventListener('pause', function () { if (engineKind === 'file') emitPlaying(false); });
      audio.addEventListener('ended', function () { if (engineKind === 'file') emitEnded(); });
      audio.addEventListener('loadedmetadata', function () { if (engineKind === 'file' && audio.duration && isFinite(audio.duration)) setDur(audio.duration); syncVideo(); });
      audio.addEventListener('loadeddata', syncVideo);
      audio.volume = volume;
      return {
        el: null,
        load: function (ref, play) { audio.src = ref || ''; setActiveHost('file', false); if (play) audio.play().catch(noop); },
        toggle: function () { if (audio.paused) audio.play().catch(noop); else audio.pause(); },
        seekFrac: function (f) { var d = audio.duration; if (d && isFinite(d)) audio.currentTime = f * d; },
        setVolume: function (v) { audio.volume = v; },
        stop: function () { audio.pause(); audio.removeAttribute('src'); try { audio.load(); } catch (e) {} }
      };
    }

    /* -- spotify engine -- */
    function spotifyEngine() {
      var wrap = mkWrap(); children.spotify = wrap;
      var ctl = null, dur = 0, ended = false, paused = true;
      function init(uri, cb) {
        if (ctl) return cb();
        withSpotify(function (api) {
          var inner = document.createElement('div'); inner.style.cssText = 'width:100%;max-width:420px;'; wrap.appendChild(inner);
          api.createController(inner, { uri: uri || 'spotify:track:x', width: '100%', height: 152 }, function (c) {
            ctl = c;
            c.addListener('playback_update', function (e) {
              if (engineKind !== 'spotify' || !e || !e.data) return;
              paused = !!e.data.isPaused; emitPlaying(!e.data.isPaused);
              dur = (e.data.duration || 0) / 1000; var pos = (e.data.position || 0) / 1000;
              emitProgress(pos, dur || playlist[idx].duration);
              if (dur > 0 && pos >= dur - 1.2 && !ended) { ended = true; emitEnded(); }
            });
            cb();
          });
        });
      }
      return {
        el: wrap,
        load: function (ref, play) {
          ended = false; dur = 0;
          init(ref, function () {
            try { ctl.loadUri(ref); } catch (e) {}
            if (play) { try { ctl.play(); } catch (e) {} setTimeout(function () { if (engineKind === 'spotify' && paused) { try { ctl.play(); } catch (e) {} } }, 600); }
          });
        },
        toggle: function () { if (ctl) try { ctl.togglePlay(); } catch (e) {} },
        seekFrac: function (f) { var d = dur || playlist[idx].duration || 0; if (ctl && d) try { ctl.seek(f * d); } catch (e) {} },
        setVolume: noop,    // Spotify volume lives inside its own embed
        stop: function () { if (ctl) try { ctl.pause(); } catch (e) {} }
      };
    }

    /* -- youtube engine -- */
    function youtubeEngine() {
      var wrap = mkWrap(); children.youtube = wrap;
      var player = null, ended = false, poll = null, YTRef = null;
      function startPoll() { stopPoll(); poll = setInterval(function () { if (engineKind !== 'youtube' || !player) return; try { var d = player.getDuration(), c = player.getCurrentTime(); emitProgress(c, d || playlist[idx].duration); } catch (e) {} }, 500); }
      function stopPoll() { if (poll) { clearInterval(poll); poll = null; } }
      function init(videoId, play, cb) {
        if (player) return cb();
        withYouTube(function (YT) {
          YTRef = YT;
          var inner = document.createElement('div'); inner.style.cssText = 'width:100%;height:100%;'; wrap.appendChild(inner);
          player = new YT.Player(inner, {
            width: '100%', height: '100%', videoId: videoId,
            playerVars: { playsinline: 1, rel: 0, modestbranding: 1, autoplay: 0 },
            events: {
              onReady: function () { try { player.setVolume(Math.round(volume * 100)); } catch (e) {} cb(); },
              onStateChange: function (e) {
                if (engineKind !== 'youtube') return;
                if (e.data === YT.PlayerState.PLAYING) { emitPlaying(true); startPoll(); }
                else if (e.data === YT.PlayerState.PAUSED) { emitPlaying(false); }
                else if (e.data === YT.PlayerState.ENDED) { emitPlaying(false); stopPoll(); if (!ended) { ended = true; emitEnded(); } }
              }
            }
          });
        });
      }
      return {
        el: wrap,
        load: function (ref, play) {
          ended = false;
          if (!player) init(ref, play, function () { if (play) { try { player.playVideo(); } catch (e) {} } });
          else { try { if (play) player.loadVideoById(ref); else player.cueVideoById(ref); } catch (e) {} }
        },
        toggle: function () { if (!player) return; try { var s = player.getPlayerState(); if (s === 1) player.pauseVideo(); else player.playVideo(); } catch (e) {} },
        seekFrac: function (f) { if (player) try { var d = player.getDuration(); if (d) player.seekTo(f * d, true); } catch (e) {} },
        setVolume: function (v) { if (player) try { player.setVolume(Math.round(v * 100)); } catch (e) {} },
        stop: function () { stopPoll(); if (player) try { player.pauseVideo(); } catch (e) {} }
      };
    }

    /* -- soundcloud engine -- */
    function soundcloudEngine() {
      var wrap = mkWrap(); children.soundcloud = wrap;
      var iframe = null, widget = null, durMs = 0, ended = false;
      function ensure(url, cb) {
        if (widget) return cb();
        withSoundCloud(function (SC) {
          iframe = document.createElement('iframe'); iframe.allow = 'autoplay'; iframe.style.cssText = 'width:100%;height:100%;border:0;';
          iframe.src = 'https://w.soundcloud.com/player/?url=' + encodeURIComponent(url) + '&auto_play=false&hide_related=true&show_comments=false&visual=true';
          wrap.appendChild(iframe);
          widget = SC.Widget(iframe);
          widget.bind(SC.Widget.Events.READY, function () {
            try { widget.setVolume(Math.round(volume * 100)); } catch (e) {}
            widget.bind(SC.Widget.Events.PLAY, function () { if (engineKind === 'soundcloud') { emitPlaying(true); widget.getDuration(function (ms) { durMs = ms; }); } });
            widget.bind(SC.Widget.Events.PAUSE, function () { if (engineKind === 'soundcloud') emitPlaying(false); });
            widget.bind(SC.Widget.Events.FINISH, function () { if (engineKind === 'soundcloud') { emitPlaying(false); if (!ended) { ended = true; emitEnded(); } } });
            widget.bind(SC.Widget.Events.PLAY_PROGRESS, function (e) { if (engineKind !== 'soundcloud') return; emitProgress((e.currentPosition || 0) / 1000, (durMs / 1000) || playlist[idx].duration); });
            cb();
          });
        });
      }
      return {
        el: wrap,
        load: function (ref, play) {
          ended = false; durMs = 0;
          if (!widget) ensure(ref, function () { widget.getDuration(function (ms) { durMs = ms; }); if (play) widget.play(); });
          else widget.load(ref, { auto_play: !!play, callback: function () { ended = false; widget.getDuration(function (ms) { durMs = ms; }); } });
        },
        toggle: function () { if (widget) widget.toggle(); },
        seekFrac: function (f) { if (widget && durMs) widget.seekTo(f * durMs); },
        setVolume: function (v) { if (widget) widget.setVolume(Math.round(v * 100)); },
        stop: function () { if (widget) try { widget.pause(); } catch (e) {} }
      };
    }

    function engineFor(kind) {
      if (P[kind]) return P[kind];
      if (kind === 'spotify') P[kind] = spotifyEngine();
      else if (kind === 'youtube') P[kind] = youtubeEngine();
      else if (kind === 'soundcloud') P[kind] = soundcloudEngine();
      else P[kind] = fileEngine();
      return P[kind];
    }

    /* -- track list -- */
    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function render() {
      if (!els.trackList) return;
      var html = '';
      playlist.forEach(function (t, i) {
        if (t.removed) {
          html += '<div class="track-item" data-i="' + i + '" data-removed="1" style="opacity:.55;">' +
            '<span class="track-num">' + String(i + 1).padStart(2, '0') + '</span>' +
            '<div class="track-info"><div class="track-title" style="text-decoration:line-through;">' + escapeHtml(t.title) + '</div>' +
            '<div style="font-size:.68rem;color:var(--hot-pink);"><i class="bi bi-slash-circle"></i> Removed by admin</div></div>' +
            '<i class="bi bi-lock-fill" style="color:var(--text-muted);"></i>' +
            '<span class="track-duration">' + fmt(t.duration) + '</span></div>';
        } else {
          html += '<div class="track-item' + (i === idx ? ' playing' : '') + '" data-i="' + i + '" style="cursor:pointer;">' +
            '<span class="track-num">' + String(i + 1).padStart(2, '0') + '</span>' +
            '<div class="track-info"><div class="track-title">' + escapeHtml(t.title) + '</div></div>' +
            (i === idx ? '<span class="badge-gv" style="font-size:.62rem;">NOW PLAYING</span>' : '<i class="bi bi-play-fill"></i>') +
            '<span class="track-duration">' + fmt(t.duration) + '</span></div>';
        }
      });
      els.trackList.innerHTML = html;
      els.trackList.querySelectorAll('.track-item').forEach(function (el) {
        if (el.dataset.removed) return;
        el.addEventListener('click', function () { loadIndex(+el.dataset.i, true); });
      });
    }

    function hasPlayable() { return playlist.some(function (t) { return !t.removed; }); }
    function seekIdx(from, dir) { if (!hasPlayable()) return -1; var n = from; for (var c = 0; c < playlist.length; c++) { n = (n + dir + playlist.length) % playlist.length; if (!playlist[n].removed) return n; } return -1; }

    function loadIndex(i, play) {
      idx = (i + playlist.length) % playlist.length;
      var t = playlist[idx];
      for (var k in P) if (P[k]) P[k].stop();    // silence every engine before switching
      if (t.removed) {
        if (play) { var p = seekIdx(idx, 1); if (p !== -1) { loadIndex(p, true); return; } }
        engineKind = 'file'; engineFor('file'); setActiveHost('file', false);
        setNP(t.title); setDur(t.duration); render(); state(); return;
      }
      engineKind = t.kind || 'file';
      var eng = engineFor(engineKind);
      setActiveHost(engineKind, false);
      setNP(t.title); setProgress(0, t.duration); render();
      eng.load(engineKind === 'file' ? t.src : t.ref, play);
      try { eng.setVolume(volume); } catch (e) {}
      state();
    }

    function next() {
      if (!hasPlayable()) return;
      if (shuffle && playlist.length > 1) {
        var pool = []; playlist.forEach(function (t, i) { if (!t.removed && i !== idx) pool.push(i); });
        if (!pool.length) { loadIndex(idx, true); return; }
        loadIndex(pool[Math.floor(Math.random() * pool.length)], true);
      } else { var n = seekIdx(idx, 1); if (n !== -1) loadIndex(n, true); }
    }

    // progress-bar scrubbing
    if (els.progress) els.progress.addEventListener('click', function (e) {
      var r = this.getBoundingClientRect(); var frac = (e.clientX - r.left) / r.width;
      if (frac < 0) frac = 0; if (frac > 1) frac = 1;
      var eng = P[engineKind]; if (eng && eng.seekFrac) eng.seekFrac(frac);
    });

    return {
      start: function () {
        var s = config.startIndex || 0;
        // When starting in shuffle mode without an explicitly chosen track,
        // begin on a random playable track instead of always track 1.
        if (shuffle && playlist.length > 1 && !config.startIndex) {
          var pool = []; playlist.forEach(function (t, i) { if (!t.removed) pool.push(i); });
          if (pool.length) s = pool[Math.floor(Math.random() * pool.length)];
        }
        if (playlist[s] && playlist[s].removed) { var fp = seekIdx(s, 1); if (fp !== -1) s = fp; }
        loadIndex(s, !!config.autoplay);
      },
      toggle: function () { var eng = engineFor(engineKind); if (eng && eng.toggle) eng.toggle(); },
      next: next,
      prev: function () { var n = seekIdx(idx, -1); if (n !== -1) loadIndex(n, true); },
      loadIndex: loadIndex,
      toggleShuffle: function () { shuffle = !shuffle; state(); return shuffle; },
      toggleRepeat: function () { repeat = !repeat; state(); return repeat; },
      setVolume: function (v) { volume = v; for (var k in P) if (P[k] && P[k].setVolume) try { P[k].setVolume(v); } catch (e) {} },
      isPlaying: function () { return playing; }
    };
  }

  GVPlayer.detectDuration = detectDuration;
  GVPlayer.classify = classify;
  global.GVPlayer = GVPlayer;
})(window);
