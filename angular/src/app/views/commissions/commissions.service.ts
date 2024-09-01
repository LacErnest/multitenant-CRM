import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import {
  CreateLog,
  IndividualCommissionPayment,
  IndividualCommissionPaymentId,
} from './interfaces/commissions-payment-log';

@Injectable({
  providedIn: 'root',
})
export class CommissionsService {
  constructor(private http: HttpClient) {}

  public getCommissionSummary(params: HttpParams): Observable<any> {
    return this.http.get('api/dashboard/commission-summary', { params });
  }

  public getCommissionPaymentLogs(params: HttpParams): Observable<any> {
    return this.http.get('api/commissions/payment_log', { params });
  }

  public getTotalOpenAmount(params: HttpParams): Observable<any> {
    return this.http.get('api/commissions/get_total_open_amount', { params });
  }

  public createCommissionPaymentLog(data: CreateLog): Observable<any> {
    return this.http.post('api/commissions/payment_log', data);
  }

  public updateCommissionPaymentLog(id: string): Observable<any> {
    return this.http.put(`api/commissions/confirm_payment/${id}`, {});
  }

  public createSalesCommissionPercentage(
    orderId: string,
    invoiceId: string,
    salesPersonId: string,
    data: { commission_percentage?: number } = {}
  ): Observable<any> {
    return this.http.post(
      `api/commissions/percentage/${orderId}/${invoiceId}/${salesPersonId}`,
      {
        ...data,
        sales_person_id: salesPersonId,
        order_id: orderId,
        invoice_id: invoiceId,
      }
    );
  }

  public updateSalesCommissionPercentage(
    orderId: string,
    invoiceId: string,
    salesPersonId: string,
    data: { commission_percentage?: number } = {}
  ): Observable<any> {
    return this.http.put(
      `api/commissions/percentage/${orderId}/${invoiceId}/${salesPersonId}`,
      data
    );
  }

  /**
   * Remove commission percentage for the given sales person
   * @param orderId
   * @param salesPersonId
   * @returns
   */
  public deleteSalesCommissionPercentage(
    orderId: string,
    invoiceId: string,
    salesPersonId: string
  ): Observable<any> {
    return this.http.delete(
      `api/commissions/percentage/${orderId}/${invoiceId}/${salesPersonId}`
    );
  }

  /**
   * Remove commission percentage for the given sales person
   * @param orderId
   * @param salesPersonId
   * @returns
   */
  public deleteSalesCommissionPercentageById(
    percentageId: string
  ): Observable<any> {
    return this.http.delete(`api/commissions/percentage/${percentageId}`);
  }

  public createIndividualCommissionPayment(
    data: IndividualCommissionPayment
  ): Observable<any> {
    return this.http.post(
      'api/commissions/individual_commission_payment',
      data
    );
  }

  public removeIndividualCommissionPayment(
    data: IndividualCommissionPaymentId
  ): Observable<any> {
    return this.http.delete(
      `api/commissions/individual_commission_payment/${data.order_id}/${data.invoice_id}/${data.sales_person_id}`
    );
  }

  public getCommissionSettings(companyId: string): Observable<any> {
    return this.http.get(`api/${companyId}/settings`);
  }

  public getProjectSalesCommissions(projectId: string): Observable<any> {
    return this.http.get(`api/${projectId}/commissions/get_total_open_amount`);
  }
}
