document.addEventListener('DOMContentLoaded', function() {
      // Booking Trends Chart
      const bookingData = bookingDataFromPHP; // This will be replaced by PHP with actual data
      const ctxBookings = document.getElementById('bookingsChart').getContext('2d');
      const gradientBookings = ctxBookings.createLinearGradient(0, 0, 0, 300);
      gradientBookings.addColorStop(0, 'rgba(16, 185, 129, 0.5)');
      gradientBookings.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

      new Chart(ctxBookings, {
        type: 'line',
        data: {
          labels: bookingData.months,
          datasets: [{
            label: 'Bookings',
            data: bookingData.counts,
            borderColor: '#10b981',
            backgroundColor: gradientBookings,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: '#1e293b',
              titleColor: '#ffffff',
              bodyColor: '#e2e8f0',
              bodyFont: {
                size: 13
              },
              padding: 12,
              cornerRadius: 8
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(226, 232, 240, 0.5)'
              },
              ticks: {
                color: '#94a3b8',
                font: {
                  size: 11
                }
              }
            },
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: '#94a3b8',
                font: {
                  size: 11
                }
              }
            }
          }
        }
      });

      // Revenue Chart
      const revenueData = revenueDataFromPHP; // This will be replaced by PHP with actual data
      const ctxRevenue = document.getElementById('revenueChart').getContext('2d');

      new Chart(ctxRevenue, {
        type: 'bar',
        data: {
          labels: revenueData.map(item => item.month),
          datasets: [{
            label: 'Revenue',
            data: revenueData.map(item => item.revenue),
            backgroundColor: '#8b5cf6',
            borderRadius: 6,
            barThickness: 12,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: '#1e293b',
              titleColor: '#ffffff',
              bodyColor: '#e2e8f0',
              callbacks: {
                label: function(context) {
                  return '$ ' + context.raw.toLocaleString();
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(226, 232, 240, 0.5)'
              },
              ticks: {
                color: '#94a3b8',
                callback: function(value) {
                  return '$' + value.toLocaleString();
                }
              }
            },
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: '#94a3b8'
              }
            }
          }
        }
      });

      // Transport Types Chart
      const transportData = transportDataFromPHP; // This will be replaced by PHP with actual data
      const ctxTransport = document.getElementById('transportChart').getContext('2d');

      new Chart(ctxTransport, {
        type: 'radar',
        data: {
          labels: ['Bookings', 'Revenue', 'Avg Price', 'Customer Rating'],
          datasets: [{
              label: 'Taxi',
              data: [
                transportData[0]?.bookings || 65,
                transportData[0]?.revenue || 12500,
                (transportData[0]?.revenue / transportData[0]?.bookings) || 192,
                4.2 * 20 // Scaling for visualization
              ],
              backgroundColor: 'rgba(245, 158, 11, 0.2)',
              borderColor: 'rgba(245, 158, 11, 1)',
              borderWidth: 2,
              pointBackgroundColor: 'rgba(245, 158, 11, 1)'
            },
            {
              label: 'Rent A Car',
              data: [
                transportData[1]?.bookings || 35,
                transportData[1]?.revenue || 22000,
                (transportData[1]?.revenue / transportData[1]?.bookings) || 628,
                4.5 * 20 // Scaling for visualization
              ],
              backgroundColor: 'rgba(16, 185, 129, 0.2)',
              borderColor: 'rgba(16, 185, 129, 1)',
              borderWidth: 2,
              pointBackgroundColor: 'rgba(16, 185, 129, 1)'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            r: {
              angleLines: {
                color: 'rgba(226, 232, 240, 0.5)'
              },
              grid: {
                color: 'rgba(226, 232, 240, 0.5)'
              },
              pointLabels: {
                color: '#64748b',
                font: {
                  size: 11
                }
              },
              ticks: {
                display: false,
                backdropColor: 'transparent'
              }
            }
          }
        }
      });

      // Hotel Location Chart
      const hotelLocationData = hotelLocationDataFromPHP; // This will be replaced by PHP with actual data
      const ctxHotelLocation = document.getElementById('hotelLocationChart').getContext('2d');

      new Chart(ctxHotelLocation, {
        type: 'doughnut',
        data: {
          labels: hotelLocationData.map(item => ucfirst(item.location)),
          datasets: [{
            data: hotelLocationData.map(item => item.hotel_count),
            backgroundColor: [
              '#10b981', // Emerald for Makkah
              '#3b82f6', // Blue for Madinah
            ],
            borderWidth: 0,
            hoverOffset: 10
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '65%',
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                padding: 15,
                font: {
                  size: 11
                }
              }
            },
            tooltip: {
              backgroundColor: '#1e293b',
              titleColor: '#ffffff',
              bodyColor: '#e2e8f0',
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = Math.round((value / total) * 100);
                  return `${label}: ${value} hotels (${percentage}%)`;
                }
              }
            }
          }
        }
      });

      // Capitalize first letter
      function ucfirst(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
      }

      // Mobile menu toggle
      document.getElementById('menu-btn').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('hidden');
      });

      // Animation for cards
      gsap.from('.rounded-xl', {
        duration: 0.8,
        opacity: 1,
        y: 20,
        stagger: 0.1,
        ease: 'power3.out'
      });
    });