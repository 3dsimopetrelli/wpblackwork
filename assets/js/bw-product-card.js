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

  // Seamless loop handler: rewinds the video ~80 ms before it ends so the
  // decoder never reaches the empty-frame boundary that causes the black flash.
  // Attached on hover-in, removed on hover-out — stored on the element itself
  // so repeated enter/leave events never stack duplicate listeners.
  function attachSeamlessLoop(video) {
    if (video._bwLoopHandler) {
      return;
    }

    video._bwLoopHandler = function () {
      if (video.duration && video.currentTime >= video.duration - 0.08) {
        video.currentTime = 0;
      }
    };

    video.addEventListener('timeupdate', video._bwLoopHandler);
  }

  function detachSeamlessLoop(video) {
    if (!video._bwLoopHandler) {
      return;
    }

    video.removeEventListener('timeupdate', video._bwLoopHandler);
    video._bwLoopHandler = null;
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
