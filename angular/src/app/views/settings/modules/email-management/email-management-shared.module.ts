import { NgModule } from '@angular/core';

import { DesignTemplateModal } from './components/design-template-modal/design-template-modal.component';
import { CommonModule } from '@angular/common';
import { DesignTemplateCard } from './components/design-template-card/design-template-card.component';
import { EmailTemplateSection } from './components/email-template-section/email-template-section.component';
import { RouterModule } from '@angular/router';
import { EmailTemplateCard } from './components/email-template-card/email-template-card.component';
import { EmailTemplateModal } from './components/email-template-modal/email-template-modal.component';
import { DesignTemplateView } from './components/design-template-view/design-template-view.component';

@NgModule({
  declarations: [
    DesignTemplateModal,
    DesignTemplateCard,
    EmailTemplateSection,
    EmailTemplateCard,
    EmailTemplateModal,
    DesignTemplateView,
  ],
  exports: [
    DesignTemplateModal,
    DesignTemplateCard,
    EmailTemplateSection,
    EmailTemplateCard,
    EmailTemplateModal,
    DesignTemplateView,
  ],
  imports: [CommonModule, RouterModule],
})
export class EmailManagementSharedModule {}
