import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import carousel from './carousel';

Alpine.plugin(collapse);
Alpine.data('carousel', carousel);

window.Alpine = Alpine;
Alpine.start();
