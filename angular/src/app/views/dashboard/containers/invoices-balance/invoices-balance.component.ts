import { Component, OnInit } from '@angular/core';
import { BalanceSheetBase } from '../../../../shared/classes/balance-sheet/balance-sheet-base';
import { DashboardService } from '../../dashboard.service';
import { GlobalService } from '../../../../core/services/global.service';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'oz-finance-invoices-balance',
  templateUrl: './invoices-balance.component.html',
  styleUrls: ['./invoices-balance.component.scss'],
})
export class InvoicesBalanceComponent
  extends BalanceSheetBase
  implements OnInit
{
  constructor(
    protected dashboardService: DashboardService,
    protected globalService: GlobalService,
    private route: ActivatedRoute,
    protected router: Router
  ) {
    super(dashboardService, globalService, router);
  }

  ngOnInit(): void {
    this.getResolvedData();
  }

  private getResolvedData() {
    this.summary = this.route.snapshot.data.summary;
    this.entity = this.route.snapshot.data.entity;
  }
}
