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
