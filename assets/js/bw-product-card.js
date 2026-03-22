(function () {
  'use strict';

  var supportsHover = window.matchMedia && window.matchMedia('(hover: hover)').matches;

  if (!supportsHover) {
    return;
  }

  function getMediaRoot(target) {
    if (!target || !(target instanceof Element)) {
      return null;
    }

    return target.closest('.bw-product-card .bw-ss__media');
  }

  function getHoverVideo(mediaRoot) {
    if (!mediaRoot) {
      return null;
    }

    return mediaRoot.querySelector('.bw-product-card-hover-video');
  }

  function isInternalTransition(mediaRoot, relatedTarget) {
    return !!(mediaRoot && relatedTarget instanceof Node && mediaRoot.contains(relatedTarget));
  }

  // Seamless loop — two layers of protection:
  //
  // 1. timeupdate (primary): browsers fire this ~4×/sec (every ~250 ms).
  //    A threshold of 80 ms is smaller than one interval, so the handler
  //    fires *after* the video has already ended. We use 300 ms so the
  //    last timeupdate before the end (at ~duration-250ms) is guaranteed
  //    to cross the threshold and rewind cleanly.
  //
  // 2. ended (fallback): if timeupdate somehow fires late, we restart
  //    immediately in the ended handler before the browser can show black.
  //
  // Both handlers are stored on the element so repeated pointer events
  // can never stack duplicate listeners.

  function attachSeamlessLoop(video) {
    if (video._bwLoopAttached) {
      return;
    }

    video._bwTimeupdateHandler = function () {
      if (video.duration && video.currentTime >= video.duration - 0.3) {
        video.currentTime = 0;
      }
    };

    video._bwEndedHandler = function () {
      video.currentTime = 0;
      var p = video.play();
      if (p && typeof p.catch === 'function') {
        p.catch(function () {});
      }
    };

    video.addEventListener('timeupdate', video._bwTimeupdateHandler);
    video.addEventListener('ended',      video._bwEndedHandler);
    video._bwLoopAttached = true;
  }

  function detachSeamlessLoop(video) {
    if (!video._bwLoopAttached) {
      return;
    }

    video.removeEventListener('timeupdate', video._bwTimeupdateHandler);
    video.removeEventListener('ended',      video._bwEndedHandler);
    video._bwTimeupdateHandler = null;
    video._bwEndedHandler      = null;
    video._bwLoopAttached      = false;
  }

  function playHoverVideo(mediaRoot) {
    var video = getHoverVideo(mediaRoot);
    if (!video) {
      return;
    }

    if (video.readyState >= 1) {
      try {
        video.currentTime = 0;
      } catch (error) {
        // Ignore browsers that temporarily block currentTime while seeking metadata.
      }
    }

    attachSeamlessLoop(video);

    var playPromise = video.play();
    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(function () {});
    }
  }

  function resetHoverVideo(mediaRoot) {
    var video = getHoverVideo(mediaRoot);
    if (!video) {
      return;
    }

    video.pause();
    detachSeamlessLoop(video);

    if (video.readyState >= 1) {
      try {
        video.currentTime = 0;
      } catch (error) {
        // Ignore browsers that temporarily block currentTime while seeking metadata.
      }
    }
  }

  document.addEventListener('pointerover', function (event) {
    var mediaRoot = getMediaRoot(event.target);

    if (!mediaRoot || isInternalTransition(mediaRoot, event.relatedTarget)) {
      return;
    }

    playHoverVideo(mediaRoot);
  });

  document.addEventListener('pointerout', function (event) {
    var mediaRoot = getMediaRoot(event.target);

    if (!mediaRoot || isInternalTransition(mediaRoot, event.relatedTarget)) {
      return;
    }

    resetHoverVideo(mediaRoot);
  });

  document.addEventListener('focusin', function (event) {
    var mediaRoot = getMediaRoot(event.target);

    if (!mediaRoot) {
      return;
    }

    playHoverVideo(mediaRoot);
  });

  document.addEventListener('focusout', function (event) {
    var mediaRoot = getMediaRoot(event.target);

    if (!mediaRoot || isInternalTransition(mediaRoot, event.relatedTarget)) {
      return;
    }

    resetHoverVideo(mediaRoot);
  });

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
      return;
    }

    document.querySelectorAll('.bw-product-card-hover-video').forEach(function (video) {
      video.pause();
      detachSeamlessLoop(video);
      if (video.readyState >= 1) {
        try {
          video.currentTime = 0;
        } catch (error) {
          // Ignore browsers that temporarily block currentTime while seeking metadata.
        }
      }
    });
  });
})();
