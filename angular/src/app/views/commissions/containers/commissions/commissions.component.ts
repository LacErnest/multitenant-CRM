import { Component, OnInit } from '@angular/core';
import { CommissionsBase } from '../../classes/commissions-base';
import { GlobalService } from '../../../../core/services/global.service';
import { CommissionsService } from '../../commissions.service';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'oz-finance-commissions',
  templateUrl: './commissions.component.html',
  styleUrls: ['./commissions.component.scss'],
})
export class CommissionsComponent extends CommissionsBase implements OnInit {
  constructor(
    protected commissionsService: CommissionsService,
    protected globalService: GlobalService,
    protected router: Router,
    private route: ActivatedRoute
  ) {
    super(commissionsService, globalService, router);
  }

  public ngOnInit(): void {
    this.getResolvedData();
  }

  private getResolvedData(): void {
    this.summary = this.route.snapshot.data.summary;
    this.entity = this.route.snapshot.data.entity;
    this.paymentLogs = this.route.snapshot.data.payment_log;
    this.totalOpenAmount = this.route.snapshot.data.total_open_amount;
  }
}
