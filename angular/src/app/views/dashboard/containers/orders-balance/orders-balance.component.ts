import { Component, OnInit } from '@angular/core';
import { DashboardService } from '../../dashboard.service';
import { GlobalService } from '../../../../core/services/global.service';
import { ActivatedRoute, Router } from '@angular/router';
import { BalanceSheetBase } from '../../../../shared/classes/balance-sheet/balance-sheet-base';

@Component({
  selector: 'oz-finance-orders-balance',
  templateUrl: './orders-balance.component.html',
  styleUrls: ['./orders-balance.component.scss'],
})
export class OrdersBalanceComponent extends BalanceSheetBase implements OnInit {
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
