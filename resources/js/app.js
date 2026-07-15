import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

import { createIcons, icons } from 'lucide';

window.Alpine = Alpine;
window.Swal = Swal;

Alpine.start();

function renderIcons() {
    createIcons({
        icons,
        attrs: {
            'stroke-width': 1.8,
        },
    });
}

document.addEventListener('DOMContentLoaded', renderIcons);
document.addEventListener('alpine:initialized', renderIcons);

window.renderIcons = renderIcons;
