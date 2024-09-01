import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { RentCost } from 'src/app/views/settings/modules/rent-costs/interfaces/rent-cost';
import { RentCostList } from 'src/app/views/settings/modules/rent-costs/interfaces/rent-cost-list';

@Injectable({
  providedIn: 'root',
})
export class RentCostsService {
  public constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public getRentCosts(params?: HttpParams): Observable<RentCostList> {
    return this.http.get<RentCostList>(
      `api/${this.globalService.currentCompany?.id}/rents`,
      { params }
    );
  }

  public getRentCost(id: string): Observable<RentCost> {
    return this.http.get<RentCost>(
      `api/${this.globalService.currentCompany?.id}/rents/${id}`
    );
  }

  public createRentCost(rentCost: RentCost): Observable<RentCost> {
    return this.http.post<RentCost>(
      `api/${this.globalService.currentCompany?.id}/rents`,
      rentCost
    );
  }

  public editRentCost(rentCost: RentCost, id: string): Observable<RentCost> {
    return this.http.patch<RentCost>(
      `api/${this.globalService.currentCompany?.id}/rents/${id}`,
      rentCost
    );
  }

  public deleteRentCost(id: string): Observable<string> {
    return this.http.delete<string>(
      `api/${this.globalService.currentCompany?.id}/rents/${id}`
    );
  }
}
