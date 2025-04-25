import es from "intl-tel-input/i18n/es";

// https://intl-tel-input.com/storybook/?path=/docs/intltelinput--vanilla
export default {
  i18n: es,
  strictMode: true,
  nationalMode: true,
  autoPlaceholder: "polite",
  placeholderNumberType: 'MOBILE',
  initialCountry: "auto",
  /**
   * Permite obtener el país del usuario actualmente conectado.
   * Hace una petición GET a https://ipapi.co/json para obtener la
   * información geográfica del usuario actual y llama al callback con
   * el código ISO 3166-1 alpha-2 del país.
   * Si falla la petición, llama al callback con el código 'mx'.
   * @param {function} callback
   */
  geoIpLookup: (callback) =>
    fetch("https://ipapi.co/json")
        .then((res) => res.json())
        .then((data) => callback(data.country_code))
        .catch(() => callback("mx")),
  loadUtils: () => import('intl-tel-input/build/js/utils.js'),
};