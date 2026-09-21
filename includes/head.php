<?php /** @var string $title */ ?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0F332E">
  <title><?= e($title ?? APP_NAME) ?> · Koperasi Mahakam Jaya</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        fontFamily: { display: ['"Bricolage Grotesque"', 'sans-serif'], sans: ['"DM Sans"', 'system-ui', 'sans-serif'] },
        colors: {
          river: { 50:'#EEF6F4', 100:'#D5ECE7', 200:'#A9D8CF', 500:'#1D9484', 600:'#14776A', 700:'#125E55', 800:'#0F4841', 900:'#0F332E', 950:'#0A231F' },
          gold:  { 100:'#FBEFCF', 400:'#EDBB45', 500:'#E0A526', 600:'#C48A0E', 800:'#7A5408' },
          clay:  { 50:'#FDF1EA', 100:'#FBE0D0', 600:'#C2410C', 700:'#9A3412' }
        }
      } }
    }
  </script>
  <style>
    body { font-family: 'DM Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
    .font-display { font-family: 'Bricolage Grotesque', 'DM Sans', sans-serif; letter-spacing: -0.02em; }
    .tabnum { font-variant-numeric: tabular-nums; }
    :focus-visible { outline: 3px solid #E0A526; outline-offset: 2px; }
    @keyframes sweep { to { transform: rotate(360deg); } }
    @keyframes ping-ring { 0% { transform: scale(.6); opacity: .7; } 100% { transform: scale(1.6); opacity: 0; } }
    .radar-sweep { transform-origin: 110px 110px; animation: sweep 7s linear infinite; }
    .btn-pulse::before { content:''; position:absolute; inset:0; border-radius:inherit; background:#E0A526; animation: ping-ring 2.2s ease-out infinite; z-index:-1; }
    #dot-user { transition: transform .8s cubic-bezier(.2,.8,.2,1); }
    dialog::backdrop { background: rgba(10,35,31,.55); backdrop-filter: blur(2px); }
    @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
  </style>
</head>
