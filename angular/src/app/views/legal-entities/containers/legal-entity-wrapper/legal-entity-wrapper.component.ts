import { ChangeDetectionStrategy, Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { LegalEntity } from 'src/app/shared/interfaces/legal-entity';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';

@Component({
  selector: 'oz-finance-legal-entity-wrapper',
  templateUrl: './legal-entity-wrapper.component.html',
  styleUrls: ['./legal-entity-wrapper.component.scss'],
})
export class LegalEntityWrapperComponent implements OnInit {
  private legalEntity: LegalEntity;
  private legalEntityId: string;

  public constructor(
    private legalEntitiesService: LegalEntitiesService,
    private route: ActivatedRoute
  ) {}

  public get formHeading(): string {
    return this.legalEntity
      ? `Legal Entity: ${this.legalEntity?.name}`
      : 'Legal Entity';
  }

  public get showLegalEntityNav(): boolean {
    return this.legalEntityId && !this.legalEntity?.deleted_at;
  }

  public ngOnInit(): void {
    this.legalEntity = this.route.snapshot?.firstChild?.data?.legalEntity;
    this.legalEntityId = this.route.snapshot?.params?.legal_entity_id;
  }
}
