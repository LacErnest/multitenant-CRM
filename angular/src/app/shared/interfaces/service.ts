export interface Service {
  id: string;
  name: string;
  price: number;
  price_unit: string;
  resource_id?: string;
}

export interface ServiceList {
  data: Service[];
  count: number;
}
