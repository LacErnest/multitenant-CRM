export interface ChartColorScheme {
  quotes: ColorSchemeDefault;
  invoices: ColorSchemeDefault;
  purchase_orders: ColorSchemeDefault;
  earnouts: ColorSchemeDefault;
  orders: ColorSchemeDefault;
}

interface ColorSchemeDefault {
  domain: string[];
}
