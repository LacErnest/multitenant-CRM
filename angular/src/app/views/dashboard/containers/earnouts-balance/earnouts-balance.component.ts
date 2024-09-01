import { Component, OnInit } from '@angular/core';
import { DashboardService } from '../../dashboard.service';
import { GlobalService } from '../../../../core/services/global.service';
import { ActivatedRoute, Router } from '@angular/router';
import { EarnoutSheetBase } from '../../../../shared/classes/earnout-sheet/earnout-sheet-base';

@Component({
  selector: 'oz-finance-earnouts-balance',
  templateUrl: './earnouts-balance.component.html',
  styleUrls: ['./earnouts-balance.component.scss'],
})
export class EarnoutsBalanceComponent
  extends EarnoutSheetBase
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

  private getResolvedData(): void {
    this.summary = this.route.snapshot.data.summary;
    this.entity = this.route.snapshot.data.entity;
    this.status = this.route.snapshot.data.status;
  }
}
