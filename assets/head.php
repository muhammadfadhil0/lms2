<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@$ICONS_VERSION/dist/tabler-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
<link rel="preconnect" href="https://rsms.me/">
<link rel="stylesheet" href="https://rsms.me/inter/inter.css">
<style>
    body {
        font-family: 'Inter', sans-serif;
    }

    .bg-orange {
        background-color: rgb(255, 99, 71);
    }

    .bg-orange-tipis {
        background-color: rgba(255, 99, 71, 0.1);
        backdrop-filter: blur(4px);
    }

    .text-orange {
        color: rgb(255, 99, 71);
    }

    .bg-orange-tipis.rounded-lg {
        border-radius: 0.5rem;
    }

    .tab-btn {
        transition: all 0.2s ease;
    }

    .tab-btn.active {
        border-color: rgb(255, 99, 71) !important;
        color: rgb(255, 99, 71) !important;
    }

    .tab-btn:not(.active) {
        border-color: transparent;
        color: #6b7280;
    }

    .tab-btn:not(.active):hover {
        color: #374151;
    }
</style>