import styled from 'styled-components';

const MainSidebar: any = styled.nav`
    width: 300px;
    flex-direction: column;
    shrink: 0;
    border-radius: 8px;
    overflow-x: hidden;
    padding: 32px;
    // position: absolute;
    margin-right: 8px;
    user-select: none;
    background: rgba(0, 0, 0, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);

    & > .openpanel-subnav-routes-wrapper {
        display: flex;
        flex-direction: column;
        font-size: 14px;

        & > a,
        & > div {
            display: flex;
            position: relative;
            padding: 12px 16px;
            gap: 12px;
            font-weight: 600;
            min-height: 48px;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            user-drag: none;
            -ms-user-drag: none;
            -moz-user-drag: none;
            -webkit-user-drag: none;
            transition: 200ms all ease-in-out;

            &.active {
                color: #fa4e49;
                fill: #fa4e49;
            }
        }
    }
`;

export default MainSidebar;
