<style>
    /* Button style */
    #scrollToTopBtn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      display: none;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 50%;
      padding-top: 10px;
      padding-bottom: 10px;
      padding-left: 15px;
      padding-right: 15px;
      font-size: 15px;
      cursor: pointer;
      z-index: 1000;
    }

    #scrollToTopBtn:hover {
      background-color: #45a049;
    }

    html {
      scroll-behavior: smooth;
    }
</style>
 <!-- Scroll to top button -->
 <button id="scrollToTopBtn"><i class="fas fa-arrow-up"></i></button>

<script>
  // Get the button
  const mybutton = document.getElementById("scrollToTopBtn");

  // When the user scrolls down 20px from the top of the document, show the button
  window.onscroll = function () {
    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
      mybutton.style.display = "block";
    } else {
      mybutton.style.display = "none";
    }
  };

  // When the user clicks the button, scroll to the top of the document
  mybutton.onclick = function () {
    document.body.scrollTop = 0; // For Safari
    document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE, and Opera
  };
</script>

