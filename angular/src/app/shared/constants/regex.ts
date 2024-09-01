export const numberOnlyRegEx = new RegExp('^[0-9]+$');
export const currencyRegEx = new RegExp(
  '^(\\d{1,3}(\\,\\d{3})*|(\\d+))(\\.\\d{0,2})?$'
);
export const floatRegEx = new RegExp('^[+-]?([0-9]*[.])?[0-9]+$');
export const positiveFloatRegEx = new RegExp('^[+]?([0-9]*[.])?[0-9]+$');
export const emailRegEx = new RegExp(
  '^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,}$'
);
export const websiteRegEx = new RegExp(
  '(https?://)?([\\da-z.-]+)\\.([a-z.]{2,6})[/\\w .-]*/?'
);
export const facebookRegEx = new RegExp(
  'http(s)?:\\/\\/([\\w]+\\.)?facebook\\.com\\/[A-z0-9_.-]+\\/?'
);
export const linkedInRegEx = new RegExp(
  'http(s)?:\\/\\/([\\w]+\\.)?linkedin\\.com\\/in\\/[A-z0-9_-]+\\/?'
);
