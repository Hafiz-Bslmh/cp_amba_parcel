<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilihan Armada</title>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>

<body>

    <x-navbar></x-navbar>
    <x-carouselarmada></x-carouselarmada>

    <!-- judul -->
    <section id="armada" class="pt-5 pb-20 md:pt-20 md:pb-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl md:text-4xl font-heading font-bold text-black mb-4">Pilihan Armada untuk Berbagai
                    Kebutuhan</h2>
                <p class="text-slate-600">ANTERA Auto Logistics Hadir dengan 4 Armada Yaitu Daihatsu Grand MAX, Daihatu
                    SIGRA, Suzuki CARRY, Mitsubisi FUSO.</p>
            </div>

            <!-- card -->
            <x-cardarmada></x-cardarmada>
            <x-benefit></x-benefit>
        </div>
    </section>

    <x-footer></x-footer>
</body>

</html>
