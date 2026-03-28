import { Component } from '@angular/core';

@Component({
  selector: 'g51-client-login',
  template: `
    <FlexboxLayout flexDirection="column" class="page">
      <StackLayout class="form p-6">
        <Image src="res://logo" height="60" class="mb-6" stretch="aspectFit"></Image>
        <Label text="Guard51 Client Portal" class="text-lg font-bold text-center mb-1"></Label>
        <Label text="Monitor your security service" class="text-xs text-muted text-center mb-6"></Label>
        <Label text="Email" class="text-xs text-muted mb-1"></Label>
        <TextField hint="email@company.com" keyboardType="email" autocorrect="false" class="input mb-3"></TextField>
        <Label text="Password" class="text-xs text-muted mb-1"></Label>
        <TextField hint="Password" secure="true" class="input mb-4"></TextField>
        <Button text="Sign In" class="btn-primary mb-3"></Button>
        <Label text="Contact your security provider for access" class="text-[10px] text-muted text-center"></Label>
      </StackLayout>
    </FlexboxLayout>
  `,
})
export class ClientLoginComponent {}
