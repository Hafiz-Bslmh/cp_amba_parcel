<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage AmbaParcel</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
    <x-navbar></x-navbar>

    <x-herosection></x-herosection>

    <x-carouselhome></x-carouselhome>

    <!-- Span Text & Gambar -->
<div class="mt-8 text-center text-2xl md:text-3xl font-bold tracking-tight text-[#1C1722]">
    Dipercaya Oleh <span class="text-[#F20D13]">1000+ Klien</span>
</div>

<div class="mt-6 flex justify-center">
    <img src="{{ asset('assets/img/logo partner.jpg') }}"
         alt="Daftar klien ANTERA"
         class="w-full max-w-5xl h-auto object-contain">
</div>

    <!-- Span Text & Gambar Tutup -->


<section id="konsultasi">
    <x-accordion></x-accordion>
</section>
    

    <x-footer></x-footer>

</body>
</html>