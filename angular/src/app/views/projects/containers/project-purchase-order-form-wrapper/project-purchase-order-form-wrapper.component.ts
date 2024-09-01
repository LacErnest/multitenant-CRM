import { Component, OnDestroy, OnInit } from '@angular/core';
import { skip, takeUntil } from 'rxjs/operators';
import { Subject } from 'rxjs';
import { Router } from '@angular/router';
import { GlobalService } from 'src/app/core/services/global.service';

@Component({
  selector: 'oz-finance-project-purchase-order-form-wrapper',
  templateUrl: './project-purchase-order-form-wrapper.component.html',
  styleUrls: ['./project-purchase-order-form-wrapper.component.scss'],
})
export class ProjectPurchaseOrderFormWrapperComponent
  implements OnInit, OnDestroy
{
  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    private router: Router,
    private globalService: GlobalService
  ) {}

  public ngOnInit(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        const navigateToDashboard = value?.id === 'all';
        this.router
          .navigate([navigateToDashboard ? '/' : `/${value.id}/projects`])
          .then();
      });
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }
}
