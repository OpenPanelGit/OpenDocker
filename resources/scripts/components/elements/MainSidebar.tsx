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

    .active {
        color: #fa4e49;
        fill: #fa4e49;
    }
`;

export default MainSidebar;
