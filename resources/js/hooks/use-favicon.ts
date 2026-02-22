import { useEffect } from 'react';
import { useBrand } from '@/contexts/BrandContext';
import { usePage } from '@inertiajs/react';

export function useFavicon() {
  const { favicon } = useBrand();
  
  useEffect(() => {
    if (!favicon) return;
    
    // Handle different favicon path formats
    let faviconUrl;
    if (favicon.startsWith('http')) {
      faviconUrl = favicon;
    } else if (favicon.includes('Product/hrmgo-saas-react/storage/media/')) {
      // Path already contains full project path, just prepend origin
      faviconUrl = `${window.location.origin}/${favicon}`;
    } else if (favicon.includes('storage/media/')) {
      // Path contains storage/media but not full project path
      const baseUrl = (window as any).baseUrl || window.location.origin;
      faviconUrl = `${baseUrl}/${favicon}`;
    } else {
      // Simple path, add full storage path
      const baseUrl = (window as any).baseUrl || window.location.origin;
      faviconUrl = `${baseUrl}/storage/media/${favicon.replace(/^\//, '')}`;
    }

    // Update favicon in document head
    let link = document.querySelector("link[rel*='icon']") as HTMLLinkElement;
    
    if (!link) {
      link = document.createElement('link');
      link.rel = 'icon';
      document.head.appendChild(link);
    }
    
    link.href = faviconUrl;
  }, [favicon]);
}