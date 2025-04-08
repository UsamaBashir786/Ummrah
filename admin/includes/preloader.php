<script>
  /**
   * Admin Dashboard Preloader
   * Simple and reliable preloader that completely removes itself after loading
   */

  // Create a self-executing function to isolate all preloader code
  (function() {
    // Create preloader div
    const preloader = document.createElement('div');
    preloader.id = 'admin-dashboard-preloader';

    // Set styles
    preloader.style.position = 'fixed';
    preloader.style.top = '0';
    preloader.style.left = '0';
    preloader.style.width = '100%';
    preloader.style.height = '100%';
    preloader.style.backgroundColor = '#6366f1';
    preloader.style.background = 'linear-gradient(-45deg, #3b82f6, #6366f1)';
    preloader.style.zIndex = '999999';
    preloader.style.display = 'flex';
    preloader.style.flexDirection = 'column';
    preloader.style.alignItems = 'center';
    preloader.style.justifyContent = 'center';
    preloader.style.transition = 'opacity 0.5s ease';

    // Create logo SVG
    const logo = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    logo.setAttribute('width', '120');
    logo.setAttribute('height', '120');
    logo.setAttribute('viewBox', '0 0 120 120');
    logo.setAttribute('fill', 'none');
    logo.style.marginBottom = '2rem';

    // Add paths
    const outerHex = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    outerHex.setAttribute('d', 'M60 10L110 40V80L60 110L10 80V40L60 10Z');
    outerHex.setAttribute('stroke', 'white');
    outerHex.setAttribute('stroke-width', '4');
    outerHex.setAttribute('fill', 'none');

    const innerHex = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    innerHex.setAttribute('d', 'M60 30L90 45V75L60 90L30 75V45L60 30Z');
    innerHex.setAttribute('stroke', 'white');
    innerHex.setAttribute('stroke-width', '3');
    innerHex.setAttribute('fill', 'none');

    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', '60');
    circle.setAttribute('cy', '60');
    circle.setAttribute('r', '15');
    circle.setAttribute('stroke', 'white');
    circle.setAttribute('stroke-width', '3');
    circle.setAttribute('fill', 'none');

    logo.appendChild(outerHex);
    logo.appendChild(innerHex);
    logo.appendChild(circle);

    // Create dots container
    const dotsContainer = document.createElement('div');
    dotsContainer.style.width = '80px';
    dotsContainer.style.height = '80px';
    dotsContainer.style.position = 'relative';
    dotsContainer.style.marginBottom = '2rem';

    // Add dots
    const positions = [{
        top: '0px',
        left: '0px'
      },
      {
        top: '0px',
        right: '0px'
      },
      {
        bottom: '0px',
        left: '0px'
      },
      {
        bottom: '0px',
        right: '0px'
      }
    ];

    positions.forEach(pos => {
      const dot = document.createElement('div');
      dot.className = 'preloader-dot';
      dot.style.position = 'absolute';
      dot.style.width = '20px';
      dot.style.height = '20px';
      dot.style.backgroundColor = 'white';
      dot.style.borderRadius = '50%';

      // Set position
      Object.keys(pos).forEach(key => {
        dot.style[key] = pos[key];
      });

      dotsContainer.appendChild(dot);
    });

    // Create loading text
    const loadingText = document.createElement('div');
    loadingText.textContent = 'Loading Dashboard';
    loadingText.style.color = 'white';
    loadingText.style.fontSize = '24px';
    loadingText.style.fontWeight = 'bold';
    loadingText.style.marginBottom = '1.5rem';
    loadingText.style.fontFamily = 'Arial, sans-serif';

    // Create progress bar container
    const progressContainer = document.createElement('div');
    progressContainer.style.width = '250px';
    progressContainer.style.height = '10px';
    progressContainer.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
    progressContainer.style.borderRadius = '10px';
    progressContainer.style.overflow = 'hidden';

    // Create progress bar
    const progressBar = document.createElement('div');
    progressBar.style.width = '0%';
    progressBar.style.height = '100%';
    progressBar.style.backgroundColor = 'white';
    progressBar.style.borderRadius = '10px';
    progressBar.style.transition = 'width 0.1s linear';

    progressContainer.appendChild(progressBar);

    // Create status text
    const statusText = document.createElement('div');
    statusText.textContent = 'Initializing...';
    statusText.style.color = 'rgba(255, 255, 255, 0.8)';
    statusText.style.fontSize = '14px';
    statusText.style.marginTop = '15px';
    statusText.style.fontFamily = 'Arial, sans-serif';

    // Add all elements to preloader
    preloader.appendChild(logo);
    preloader.appendChild(dotsContainer);
    preloader.appendChild(loadingText);
    preloader.appendChild(progressContainer);
    preloader.appendChild(statusText);

    // Add preloader to body
    document.body.appendChild(preloader);

    // Set up dot animation
    const dots = document.querySelectorAll('.preloader-dot');
    let growing = true;

    const animateDots = () => {
      dots.forEach((dot, index) => {
        setTimeout(() => {
          if (growing) {
            dot.style.transform = 'scale(0.7)';
            dot.style.opacity = '0.7';
          } else {
            dot.style.transform = 'scale(1)';
            dot.style.opacity = '1';
          }
        }, index * 150);
      });
      growing = !growing;
    };

    // Initial dot animation
    animateDots();
    const dotInterval = setInterval(animateDots, 1000);

    // Progress bar animation
    const statusMessages = [
      "Initializing...",
      "Loading resources...",
      "Preparing dashboard...",
      "Almost there...",
      "Finishing up...",
      "Ready!"
    ];

    let progress = 0;
    const progressInterval = setInterval(() => {
      progress += 1;
      progressBar.style.width = `${progress}%`;

      // Update status messages
      if (progress === 20) {
        statusText.textContent = statusMessages[1];
      } else if (progress === 40) {
        statusText.textContent = statusMessages[2];
      } else if (progress === 60) {
        statusText.textContent = statusMessages[3];
      } else if (progress === 80) {
        statusText.textContent = statusMessages[4];
      } else if (progress === 100) {
        statusText.textContent = statusMessages[5];

        // Complete loading
        clearInterval(progressInterval);
        clearInterval(dotInterval);

        // Fade out and remove after reaching 100%
        setTimeout(() => {
          preloader.style.opacity = '0';

          // Remove from DOM after fade out
          setTimeout(() => {
            if (preloader.parentNode) {
              preloader.parentNode.removeChild(preloader);
              console.log("Preloader completely removed");
            }
          }, 500);
        }, 800);
      }
    }, 30);

    // Failsafe: Force remove preloader after 10 seconds if something goes wrong
    setTimeout(() => {
      if (preloader.parentNode) {
        clearInterval(progressInterval);
        clearInterval(dotInterval);
        preloader.parentNode.removeChild(preloader);
        console.log("Preloader force removed by failsafe");
      }
    }, 10000);
  })();
</script>