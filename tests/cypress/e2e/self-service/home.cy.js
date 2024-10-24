/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

describe('Helpdesk home page', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Self-Service');
    });
    it('can use tiles', () => {
        cy.visit('/Home');
        cy.findByRole('region', { name: 'Quick Access' })
            .findAllByRole('link')
            .as('tiles');

        // Each links must lead to a valid page (status code 200)
        cy.get('@tiles').each(($tile) => {
            cy.request({
                url: $tile.attr('href'),
                failOnStatusCode: true,
            });
        });
    });

    it('can use tabs', () => {
        const next_year = (new Date().getFullYear() + 1);

        // Create test data set
        cy.createWithAPI('Ticket', {
            'users_id': 7,
            'name': 'Open ticket 1',
            'content': 'Open ticket 1',
            'entities_id': 1,
        });
        cy.createWithAPI('Ticket', {
            'users_id': 7,
            'name': 'Open ticket 2',
            'content': 'Open ticket 2',
            'entities_id': 1,
        });
        cy.createWithAPI('Ticket', {
            'users_id': 7,
            'name': 'Closed ticket 1',
            'content': 'Closed ticket 1',
            'entities_id': 1,
            'status': 5,
        });
        cy.createWithAPI('Reminder', {
            'users_id': 7,
            'name': 'Public reminder 1',
            'content': 'Public reminder 1',
            'begin': '2023-10-01 16:45:11',
            'end': `${next_year}-10-01 16:45:11`,
        }).as('reminder_id');
        cy.get('@reminder_id').then(reminder_id => {
            cy.createWithAPI('Reminder_User', {
                'users_id': 7,
                'reminders_id': reminder_id,
            }).as('reminder_id');
        });
        cy.visit('/Home');

        // Default tab should be opened tickets
        cy.findAllByText('Open ticket 1').should('be.visible');
        cy.findAllByText('Open ticket 2').should('be.visible');
        cy.findAllByText('Closed ticket 1').should('not.exist');
        cy.findByRole('tabpanel').within(() => {
            // Validate the default columns are displayed
            cy.findAllByRole('columnheader').should('have.length', 6);
            cy.findByRole('columnheader', {'name': 'ID'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Title'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Entity'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Status'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Last update'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Opening date'}).should('be.visible');
        });

        // Got to closed tickets tab
        cy.findByRole('tab', {'name': 'Solved tickets'}).click();
        cy.findAllByText('Open ticket 1').should('not.be.visible');
        cy.findAllByText('Open ticket 2').should('not.be.visible');
        cy.findAllByText('Closed ticket 1').should('be.visible');
        cy.findByRole('tabpanel').within(() => {
            // Validate the default columns are displayed
            cy.findAllByRole('columnheader').should('have.length', 6);
            cy.findByRole('columnheader', {'name': 'ID'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Title'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Entity'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Status'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Last update'}).should('be.visible');
            cy.findByRole('columnheader', {'name': 'Opening date'}).should('be.visible');
        });

        // Got to Reminder Feed tab
        cy.findByRole('tab', {'name': 'Reminders'}).click();
        cy.findAllByRole('link', {'name': 'Public reminder 1'}).should('be.visible');

        // Return to main tab, make it easier to re-run the test as the last tab
        // is kept in the session
        cy.findByRole('tab', {'name': 'Ongoing tickets'}).click();

        // RSS feeds are not tested as they are only displayed if a real feed
        // is configurated. Since the query to the feed is done on the backend,
        // we can't mock it here.
        // Could be added if we don't mind relying on a real outside feeds for
        // ours tests or if we setup a dedicated container for this.
    });
});
