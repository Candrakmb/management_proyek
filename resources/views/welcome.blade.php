<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Task Management</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    </head>
    <body>

        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
          <div class="container">
            <a class="navbar-brand" href="#">TaskManager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
              <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
              <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                  <a class="nav-link active" href="#features">Fitur</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#projects">Proyek</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#contact">Kontak</a>
                </li>
              </ul>
            </div>
          </div>
        </nav>

        <!-- Hero Section -->
        <section class="py-5 text-center bg-light">
          <div class="container">
            <h1 class="display-4">Kelola Tugas dan Proyek Anda Lebih Mudah</h1>
            <p class="lead">TaskManager membantu tim Anda mengatur tugas harian, memantau progres proyek, dan meningkatkan kolaborasi.</p>

            @if (Route::has('login'))
            <div>
                @auth
                    <a href="{{ url('/admin') }}" class="text-gray-700 hover:text-blue-600 px-4 py-2 rounded-md text-sm font-medium">Dashboard</a>
                @else
                    <a href="/admin/login" class="btn btn-primary btn-lg">Mulai Sekarang</a>
                @endauth
            </div>
        @endif
            <a href="/admin/login" class="btn btn-primary btn-lg">Mulai Sekarang</a>
          </div>
        </section>

        <!-- Features Section -->
        <section class="py-5" id="features">
          <div class="container">
            <div class="row text-center">
              <div class="col-md-4">
                <h4>Board View</h4>
                <p>Kelola tugas dengan tampilan kanban, drag-and-drop antar status.</p>
              </div>
              <div class="col-md-4">
                <h4>Timeline</h4>
                <p>Lihat proyek dalam tampilan timeline untuk memudahkan perencanaan.</p>
              </div>
              <div class="col-md-4">
                <h4>Role Management</h4>
                <p>Atur hak akses pengguna berdasarkan peran (Admin, Manager, Staff).</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Projects Section -->
        <section class="py-5 bg-light" id="projects">
          <div class="container">
            <div class="text-center mb-5">
              <h2>Manajemen Proyek</h2>
              <p>Kelola proyek, tambahkan anggota, dan pantau progres dengan mudah.</p>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="card mb-4">
                  <div class="card-body">
                    <h5 class="card-title">Buat Proyek Baru</h5>
                    <p class="card-text">Buat dan kelola proyek dari awal, tetapkan tanggal mulai dan deadline.</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card mb-4">
                  <div class="card-body">
                    <h5 class="card-title">Tambahkan Anggota</h5>
                    <p class="card-text">Assign user ke proyek dan tugas untuk meningkatkan kolaborasi tim.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Contact Section -->
        <section class="py-5" id="contact">
          <div class="container text-center">
            <h2>Hubungi Kami</h2>
            <p>Ingin tahu lebih lanjut? Hubungi kami untuk demo sistem.</p>
            <a href="mailto:support@taskmanager.com" class="btn btn-outline-primary">Email Kami</a>
          </div>
        </section>

        <!-- Footer -->
        <footer class="py-3 bg-primary text-white text-center">
          <div class="container">
            <small>&copy; 2025 Candra Kusuma Muhammad Bimantara. Semua hak dilindungi.</small>
          </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        </body>
</html>
