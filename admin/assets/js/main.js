// Enhanced sidebar toggle with animations
document.getElementById("menu-btn").addEventListener("click", function() {
  const sidebar = document.getElementById("sidebar");
  
  if (sidebar.classList.contains('hidden')) {
    sidebar.classList.remove('hidden');
    gsap.fromTo("#sidebar", 
      { x: -100, opacity: 0 },
      { duration: 0.5, x: 0, opacity: 1, ease: "power3.out" }
    );
  } else {
    gsap.to("#sidebar", {
      duration: 0.5,
      x: -100,
      opacity: 0,
      ease: "power3.in",
      onComplete: () => {
        sidebar.classList.add('hidden');
      }
    });
  }
});

// Enhanced close button functionality
document.getElementById("close-sidebar").addEventListener("click", function() {
  const sidebar = document.getElementById("sidebar");
  
  gsap.to("#sidebar", {
    duration: 0.5,
    x: -100,
    opacity: 0,
    ease: "power3.in",
    onComplete: () => {
      sidebar.classList.add('hidden');
      // Remove any inline styles added by GSAP
      sidebar.style.transform = '';
      sidebar.style.opacity = '';
    }
  });
});

// Function to handle dropdown animations
function toggleDropdown(dropdownId) {
  const dropdown = document.getElementById(dropdownId);
  const chevron = dropdown.previousElementSibling.querySelector('.fa-chevron-down');
  
  if (dropdown.classList.contains('hidden')) {
    // Show dropdown
    dropdown.classList.remove('hidden');
    gsap.fromTo(dropdown, 
      { height: 0, opacity: 0 },
      { 
        height: 'auto',
        opacity: 1,
        duration: 0.3,
        ease: "power2.out"
      }
    );
    
    // Animate dropdown links
    gsap.fromTo(dropdown.querySelectorAll('a'),
      { y: -10, opacity: 0 },
      { 
        y: 0,
        opacity: 1,
        duration: 0.3,
        stagger: 0.05,
        ease: "power2.out"
      }
    );
    
    // Rotate chevron down
    gsap.to(chevron, {
      duration: 0.3,
      rotation: 180
    });
  } else {
    // Hide dropdown
    gsap.to(dropdown, {
      height: 0,
      opacity: 0,
      duration: 0.3,
      ease: "power2.in",
      onComplete: () => {
        dropdown.classList.add('hidden');
        // Reset height after animation
        dropdown.style.height = '';
      }
    });
    
    // Rotate chevron up
    gsap.to(chevron, {
      duration: 0.3,
      rotation: 0
    });
  }
}
