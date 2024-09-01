export interface Analytics {
  earnouts: {
    chosen_period: {
      data: {
        name: string;
        value: number;
        extra: {
          loan_substraction: number;
        }[];
      };
    };
  };
  invoices: {
    chosen_period: {
      awaiting_payment: AnalyticsAdditionalInfo;
      data: InvoiceDataItem[];
      drafts: AnalyticsAdditionalInfo;
      overdue: AnalyticsAdditionalInfo;
      paid: AnalyticsAdditionalInfo;
      total_invoices: AnalyticsAdditionalInfo;
    };
    year_before: InvoiceDataItem[];
  };
  orders: {
    chosen_period: {
      data: OrderDataItem[];
      delivered: AnalyticsAdditionalInfo;
      drafts: AnalyticsAdditionalInfo;
      total_orders: AnalyticsAdditionalInfo;
    };
    year_before: OrderDataItem[];
  };
  purchase_orders: {
    chosen_period: {
      awaiting_payment: AnalyticsAdditionalInfo;
      data: PurchaseOrderDataItem[];
      drafts: AnalyticsAdditionalInfo;
      paid: AnalyticsAdditionalInfo;
      total_purchase_orders: AnalyticsAdditionalInfo;
    };
    year_before: PurchaseOrderDataItem[];
  };
  quotes: {
    chosen_period: {
      awaiting_approval: AnalyticsAdditionalInfo;
      data: QuoteDataItem[];
      declined: AnalyticsAdditionalInfo;
      drafts: AnalyticsAdditionalInfo;
      total_quotes: AnalyticsAdditionalInfo;
    };
    year_before: QuoteDataItem[];
  };
}

interface AnalyticsAdditionalInfo {
  count: number;
  monetary_value: number;
  vat_value: number;
}

interface InvoiceDataItem {
  name: string;
  series: {
    name: 'production_costs' | 'general_costs' | 'net_profit';
    revenue: number;
    value: number;
    gross_margin?: number;
    intra_company_revenue: number;
    intra_company_gm?: number;
  }[];
}

interface OrderDataItem {
  name: string;
  series: {
    name: 'gross_margin' | 'costs';
    revenue: number;
    revenue_vat: number;
    value: number;
  }[];
}

interface PurchaseOrderDataItem {
  name: string;
  series: {
    name: string;
    quantity: number;
    value: number;
    vat_value: number;
  }[];
}

interface QuoteDataItem {
  name: string;
  series: {
    name: 'gross_margin' | 'costs';
    revenue: number;
    revenue_vat: number;
    value: number;
  }[];
}
