import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { OrderStatus } from 'src/app/views/projects/modules/project/enums/order-status.enum';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';

@Component({
  selector: 'oz-finance-project-nav',
  templateUrl: './project-nav.component.html',
  styleUrls: ['./project-nav.component.scss'],
})
export class ProjectNavComponent implements OnInit, OnDestroy {
  public userRole: number;
  public userRoleEnum = UserRole;
  public orderStatusEnum = OrderStatus;
  public project: Project;
  public purchaseOrderProject = false;

  private onDestroy$: Subject<void> = new Subject<void>();

  constructor(
    private globalService: GlobalService,
    private projectService: ProjectService,
    private route: ActivatedRoute
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  private getResolvedData(): void {
    this.userRole = this.globalService.getUserRole();
    this.project = this.route.snapshot.data.project;
    this.purchaseOrderProject = this.project.purchase_order_project;
  }

  private initSubscriptions(): void {
    this.projectService.project
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(value => {
        this.project = value;
      });
  }
}
