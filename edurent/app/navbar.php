<?php
// define navbar
$menuItems = [
    ['label' => translate('word_reservations'), 'href' => 'admini', 'visible' => true],
    ['label' => translate('word_orderHistory'), 'href' => 'orderhistory', 'visible' => true],
    ['label' => translate('word_departments'), 'href' => 'departments', 'visible' => true],
    ['label' => translate('word_faq'), 'href' => 'faq', 'visible' => true],
    ['label' => translate('word_admins'), 'href' => 'admins', 'visible' => $is_superadmin],
    ['label' => translate('word_logs'), 'href' => 'logs', 'visible' => $is_superadmin],
    ['label' => translate('word_settings'), 'href' => 'update_settings', 'visible' => $is_superadmin],
];

$menuItemsHtml = '';
foreach ($menuItems as $item) {
    if ($item['visible']) {
        $menuItemsHtml .= '<li class="nav-item">';
        $menuItemsHtml .= '<a class="nav-link" href="' . htmlspecialchars($item['href']) . '">' . htmlspecialchars($item['label']) . '</a>';
        $menuItemsHtml .= '</li>';
    }
}
?>

<!-- Navbar HTML -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
  <div class="container">

  <!-- margin hinzufügen -->

    <!-- toggle button -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" 
            aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- menu-->
    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav mx-auto" id="navbarMenu">
        <?= $menuItemsHtml ?>
      </ul>
    </div>
  </div>
</nav>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const links = document.querySelectorAll('#navbarMenu .nav-link');
    const currentPath = window.location.pathname.toLowerCase()
        .replace(/^\/edurent\//, '')
        .replace(/\.php$/, '');

    links.forEach(link => {
        const linkPath = link.getAttribute('href').toLowerCase();

        if (currentPath == linkPath) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
});
</script>

