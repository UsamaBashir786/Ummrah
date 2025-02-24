<div class="preloader">
  <div class="loader">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
  </div>
</div>

<style>
  .preloader {
    position: fixed;
    width: 100%;
    height: 100vh;
    background: #ffffff; /* Change to your background color */
    display: flex;
    align-items: center;
    justify-content: center;
    top: 0;
    left: 0;
    z-index: 9999; /* Ensures it stays above other content */
  }

  .loader {
    --color: #a5a5b0;
    --size: 70px;
    width: var(--size);
    height: var(--size);
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
  }

  .loader span {
    width: 100%;
    height: 100%;
    background-color: var(--color);
    animation: keyframes-blink 0.6s alternate infinite linear;
  }

  .loader span:nth-child(1) { animation-delay: 0ms; }
  .loader span:nth-child(2) { animation-delay: 200ms; }
  .loader span:nth-child(3) { animation-delay: 300ms; }
  .loader span:nth-child(4) { animation-delay: 400ms; }
  .loader span:nth-child(5) { animation-delay: 500ms; }
  .loader span:nth-child(6) { animation-delay: 600ms; }

  @keyframes keyframes-blink {
    0% {
      opacity: 0.3;
      transform: scale(0.5) rotate(5deg);
    }
    50% {
      opacity: 1;
      transform: scale(1);
    }
  }
</style>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const preloader = document.querySelector(".preloader");

    window.onload = function () {
      setTimeout(() => {
        preloader.style.opacity = "0";
        preloader.style.transition = "opacity 0.5s ease-out";

        setTimeout(() => {
          preloader.style.display = "none";
        }, 500);
      }, 2000);
    };
  });
</script>
