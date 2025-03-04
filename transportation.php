<?php
session_start();
include 'connection/connection.php';

function getTransportationByCategory($category)
{
  global $conn;
  $sql = "SELECT * FROM transportation WHERE category = ? AND status != 'deleted'";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $category);
  $stmt->execute();
  return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$categories = ['luxury', 'vip', 'shared'];
$transportation_data = [];
foreach ($categories as $category) {
  $transportation_data[$category] = getTransportationByCategory($category);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link rel="stylesheet" href="assets/css/transportation.css">
  <link href="https://unpkg.com/boxicons/css/boxicons.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.1/gsap.min.js"></script>
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php'; ?>
  <div class="my-5"></div>

  <section class="hero text-center py-10">
    <div class="container mx-auto px-6">
      <h1 class="text-5xl font-bold">Umrah Transportation</h1>
      <p class="text-xl text-gray-600">Seamless travel solutions for your sacred journey.</p>
    </div>
  </section>

  <section class="py-10 bg-gray-100 text-center">
    <div class="container mx-auto px-6">
      <h2 class="text-4xl font-semibold text-teal-700 mb-6">Our Services</h2>
      <div class="filter-btns flex justify-center gap-4 mb-6">
        <?php foreach ($categories as $category): ?>
          <button id="<?php echo $category; ?>-btn"
            class="filter-btn px-5 py-2 rounded-lg bg-teal-600 text-white <?php echo $category === 'luxury' ? 'active' : ''; ?>"
            onclick="filterTransport('<?php echo $category; ?>')">
            <?php echo ucfirst($category) . ' ' . ($category === 'shared' ? 'Bus' : 'Shuttle'); ?>
          </button>
        <?php endforeach; ?>

      </div>

      <div class="grid md:grid-cols-3 gap-8">
        <?php foreach ($transportation_data as $category => $vehicles): ?>
          <?php foreach ($vehicles as $vehicle): ?>
            <div class="p-6 bg-white shadow-lg rounded-lg transport-card <?php echo $category; ?> <?php echo $category !== 'luxury' ? 'hidden' : ''; ?>">
              <span class="badge text-sm bg-teal-500 text-white px-3 py-1 rounded-lg"><?php echo ucfirst($category); ?></span>
              <img src="admin/<?php echo htmlspecialchars($vehicle['transport_image']); ?>" class="rounded-lg mb-4 w-full h-48 object-cover">
              <h3 class="text-2xl font-bold mb-2"> <?php echo htmlspecialchars($vehicle['transport_name']); ?> </h3>
              <p class="text-gray-600"> <?php echo htmlspecialchars($vehicle['details']); ?> </p>
              <p class="mt-2 font-semibold <?php echo strtolower($vehicle['status']); ?>">
                <?php echo ucfirst($vehicle['status']); ?>
              </p>

              <p class="timing text-gray-700">
                <?php echo $vehicle['status'] === 'available' ? 'Available from ' . $vehicle['time_from'] . ' to ' . $vehicle['time_to'] : 'Unavailable'; ?>
              </p>

              <div class="mt-4 flex justify-between items-center">
                <span class="text-xl font-bold text-teal-600">$
                  <?php echo number_format($vehicle['price'], 2); ?>
                </span>
                <?php if (!empty($vehicle['id']) && isset($vehicle['id'])): ?>
                  <button class="btn-primary text-white bg-teal-600 hover:bg-teal-700 transition duration-300 
        <?php echo $vehicle['status'] === 'booked' ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-lg'; ?>"
                    onclick="window.location.href='transportation-booking.php?id=<?php echo urlencode($vehicle['id'] ?? ''); ?>'"
                    <?php echo $vehicle['status'] === 'booked' ? 'disabled' : ''; ?>>
                    <?php echo $vehicle['status'] === 'booked' ? 'Fully Booked' : 'Book Now'; ?>
                  </button>
                <?php else: ?>
                  <button class="btn-primary text-white bg-gray-400 cursor-not-allowed" disabled>
                    Not Available
                  </button>
                <?php endif; ?>


              </div>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="py-20 text-center">
    <div class="container mx-auto px-6">
      <h2 class="text-4xl font-semibold text-teal-700 mb-6">Get in Touch</h2>
      <p class="text-lg text-gray-600">Need assistance? Contact us for bookings and inquiries.</p>
    </div>
  </section>

  <script>
    function filterTransport(type) {
      document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
      document.getElementById(type + '-btn').classList.add('active');

      document.querySelectorAll('.transport-card').forEach(card => card.classList.add('hidden'));
      document.querySelectorAll('.' + type).forEach(card => {
        card.classList.remove('hidden');
        gsap.fromTo(card, {
          opacity: 0,
          y: 100
        }, {
          opacity: 1,
          y: 0,
          duration: 0.5
        });
      });
    }
  </script>
</body>

</html>