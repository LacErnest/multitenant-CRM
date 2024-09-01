import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { ProjectQuoteService } from '../services/project-quote.service';

@Injectable({
  providedIn: 'root',
})
export class QuoteResolver implements Resolve<any> {
  constructor(private projectQuoteService: ProjectQuoteService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    return this.projectQuoteService.getProjectQuote(
      route.parent.parent.params.project_id,
      route.params.quote_id
    );
  }
}
