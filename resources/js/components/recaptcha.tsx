import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';

interface RecaptchaProps {
  onVerify: (token: string) => void;
  onExpired?: () => void;
  onError?: () => void;
}

declare global {
  interface Window {
    grecaptcha: any;
    onRecaptchaLoad: () => void;
  }
}

export default function Recaptcha({ onVerify, onExpired, onError }: RecaptchaProps) {
  const { settings = {} } = usePage().props as any;
  const recaptchaRef = useRef<HTMLDivElement>(null);
  const widgetId = useRef<number | null>(null);
  const containerId = useRef<string>(`recaptcha-${Math.random().toString(36).substr(2, 9)}`);

  const recaptchaEnabled = settings.recaptchaEnabled === 'true' || settings.recaptchaEnabled === true || settings.recaptchaEnabled === 1 || settings.recaptchaEnabled === '1';
  const recaptchaVersion = settings.recaptchaVersion || 'v2';
  const recaptchaSiteKey = settings.recaptchaSiteKey || '';

  useEffect(() => {
    if (!recaptchaEnabled || !recaptchaSiteKey) return;

    const loadRecaptcha = () => {
      try {
        if (recaptchaVersion === 'v2') {
          loadRecaptchaV2();
        } else {
          loadRecaptchaV3();
        }
      } catch (error) {
        console.error('ReCaptcha load error:', error);
        if (onError) onError();
      }
    };

    const loadRecaptchaV2 = () => {
      if (window.grecaptcha && recaptchaRef.current && widgetId.current === null) {
        try {
          widgetId.current = window.grecaptcha.render(containerId.current, {
            sitekey: recaptchaSiteKey,
            callback: onVerify,
            'expired-callback': onExpired || (() => {}),
            'error-callback': onError || (() => {}),
          });
        } catch (error) {
          console.error('ReCaptcha v2 render error:', error);
        }
      }
    };

    const loadRecaptchaV3 = () => {
      if (window.grecaptcha) {
        try {
          window.grecaptcha.ready(() => {
            window.grecaptcha.execute(recaptchaSiteKey, { action: 'submit' })
              .then((token: string) => {
                onVerify(token);
              })
              .catch((error: any) => {
                console.error('ReCaptcha v3 execute error:', error);
                if (onError) onError();
              });
          });
        } catch (error) {
          console.error('ReCaptcha v3 ready error:', error);
        }
      }
    };

    if (window.grecaptcha) {
      loadRecaptcha();
    } else {
      window.onRecaptchaLoad = loadRecaptcha;
      const script = document.createElement('script');
      script.src = `https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=${recaptchaVersion === 'v3' ? recaptchaSiteKey : 'explicit'}`;
      script.async = true;
      script.defer = true;
      script.onerror = () => {
        console.error('Failed to load ReCaptcha script');
        if (onError) onError();
      };
      document.head.appendChild(script);
    }

    return () => {
      try {
        if (widgetId.current !== null && window.grecaptcha) {
          window.grecaptcha.reset(widgetId.current);
          widgetId.current = null;
        }
      } catch (error) {
        console.error('ReCaptcha cleanup error:', error);
      }
    };
  }, [recaptchaEnabled, recaptchaSiteKey]);

  if (!recaptchaEnabled || !recaptchaSiteKey) {
    return null;
  }

  return recaptchaVersion === 'v2' ? <div id={containerId.current} ref={recaptchaRef}></div> : null;
}